<?php

namespace Modules\TrainingSchedule\Services;

use App\Services\DigitalSignatureService;
use App\Support\TrainingDept;
use App\Support\WordExportTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Class\Models\ClassModel;
use Modules\ExportTemplates\Data\LhlScheduleGroupService;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Exceptions\ActiveTemplateNotFoundException;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Services\ActiveTemplateResolver;
use Modules\ExportTemplates\Services\TemplateRenderService;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\TrainingSchedule\Exports\FacultyTrainingPlanExport;
use Modules\TrainingSchedule\Exports\GroupedTrainingPlanFromTemplateExport;
use Modules\TrainingSchedule\Exports\TrainingPlanFromTemplateExport;
use Modules\TrainingSchedule\Exports\TrainingPlanPdfExport;
use Modules\TrainingSchedule\Exports\TrainingPlanWordExport;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingExportService
{
    public function __construct(
        private readonly ActiveTemplateResolver $activeTemplateResolver,
        private readonly TemplateRenderService $templateRenderService,
        private readonly LhlScheduleGroupService $scheduleGroupService,
        private readonly LhlPeriodLayoutSelector $layoutSelector
    ) {}

    /**
     * Xuất lịch huấn luyện (PDOT) — Excel hoặc Word theo mẫu HK2 25-26
     * (lưới tuần, gạch chéo ngày/tiết, header/footer, 3 chữ ký).
     *
     * @param  array<int|string>  $trainingScheduleIds
     * @param  array<string, mixed>  $meta
     */
    public function exportTrainingCalendar(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = [],
        string $format = 'xlsx',
    ): Response {
        $format = strtolower($format);
        if ($format === 'pdf') {
            return $this->exportTrainingCalendarPdf(
                $trainingScheduleIds,
                $startDate,
                $endDate,
                $meta
            );
        }

        $format = in_array($format, ['docx', 'word'], true) ? 'docx' : 'xlsx';
        $outputFormat = $format === 'docx' ? OutputFormat::WORD : OutputFormat::EXCEL;
        $layout = $this->layoutSelector->select(
            $trainingScheduleIds,
            $startDate,
            $endDate
        );
        $featureKey = $this->layoutSelector->featureKey($layout);

        if ((bool) config('lhl_export.template_engine_enabled', true)) {
            $activeVersion = $this->activeTemplateResolver->find(
                $featureKey,
                $outputFormat
            );

            if ($activeVersion) {
                try {
                    $path = $this->templateRenderService->renderActive(
                        $featureKey,
                        $outputFormat,
                        array_merge($meta, [
                            'training_schedule_ids' => $trainingScheduleIds,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'export_meta' => $meta,
                            'generated_by' => Auth::user()?->name ?? 'Hệ thống',
                        ]),
                        Auth::id()
                    );
                    if ($outputFormat === OutputFormat::WORD && $layout === LhlPeriodLayoutSelector::GROUPED_PERIODS) {
                        $this->assertGroupedWordKeepsEveryPeriodRange(
                            $path,
                            $trainingScheduleIds,
                            $startDate,
                            $endDate
                        );
                    }
                    $filename = 'Lich_Huan_Luyen_'
                        .Carbon::parse($startDate)->format('Ymd').'_'
                        .Carbon::parse($endDate)->format('Ymd').'.'.$format;

                    return response()
                        ->download($path, $filename, [
                            'X-Template-Engine' => 'active',
                            'X-Template-Version' => (string) $activeVersion->version_number,
                            'X-LHL-Layout' => $layout,
                            'X-LHL-Template-Feature' => $featureKey,
                        ])
                        ->deleteFileAfterSend(true);
                } catch (\Throwable $exception) {
                    $this->recordTemplateFallback(
                        $outputFormat,
                        'render_failed',
                        $exception,
                        $activeVersion->id,
                        $featureKey
                    );

                    if (! (bool) config('lhl_export.template_engine_fallback', true)) {
                        throw $exception;
                    }
                }
            } elseif (! (bool) config('lhl_export.template_engine_fallback', true)) {
                throw new ActiveTemplateNotFoundException(
                    "Không có template Active [{$featureKey}] định dạng [{$outputFormat->value}]."
                );
            } else {
                $this->recordTemplateFallback(
                    $outputFormat,
                    'active_template_missing',
                    featureKey: $featureKey
                );
            }
        }

        if ($format === 'docx') {
            $response = $this->exportTrainingCalendarWordGrid(
                $trainingScheduleIds,
                $startDate,
                $endDate,
                $meta,
                $layout
            );
        } else {
            $response = $this->exportTrainingCalendarExcel(
                $trainingScheduleIds,
                $startDate,
                $endDate,
                $meta,
                $layout
            );
        }

        $response->headers->set('X-Template-Engine', 'legacy-fallback');
        $response->headers->set('X-LHL-Layout', $layout);
        $response->headers->set('X-LHL-Template-Feature', $featureKey);

        return $response;
    }

    /**
     * PDF được dựng trực tiếp bằng mPDF từ cùng payload/thuật toán nhóm tiết,
     * hoàn toàn không phụ thuộc DOCX hoặc LibreOffice.
     *
     * @param  array<int|string>  $trainingScheduleIds
     * @param  array<string, mixed>  $meta
     */
    public function exportTrainingCalendarPdf(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = [],
    ): BinaryFileResponse {
        $pdfPath = $this->temporaryExportPath('lhl_pdf_', 'pdf');
        $layout = $this->layoutSelector->select($trainingScheduleIds, $startDate, $endDate);
        $featureKey = $this->layoutSelector->featureKey($layout);

        try {
            $payload = $this->buildTrainingPlanPayload($trainingScheduleIds, $startDate, $endDate, $meta);
            (new TrainingPlanPdfExport(
                $payload['classes'],
                $payload['meta'],
                $layout
            ))->savePdf($pdfPath);

            $filename = 'Lich_Huan_Luyen_'
                .Carbon::parse($startDate)->format('Ymd').'_'
                .Carbon::parse($endDate)->format('Ymd').'.pdf';

            return response()->download($pdfPath, $filename, [
                'X-PDF-Source' => 'mpdf',
                'X-Template-Engine' => 'mpdf-native',
                'X-LHL-Layout' => $layout,
                'X-LHL-Template-Feature' => $featureKey,
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }

            throw $exception;
        }
    }

    private function temporaryExportPath(string $prefix, string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), $prefix);
        if ($base === false) {
            throw new \RuntimeException('Không thể tạo file tạm để xuất LHL.');
        }
        @unlink($base);

        return $base.'.'.ltrim($extension, '.');
    }

    private function recordTemplateFallback(
        OutputFormat $format,
        string $reason,
        ?\Throwable $exception = null,
        ?int $versionId = null,
        string $featureKey = LhlPeriodLayoutSelector::CLASSIC_FEATURE_KEY
    ): void {
        Log::warning('LHL template engine fallback', [
            'format' => $format->value,
            'reason' => $reason,
            'template_version_id' => $versionId,
            'feature_key' => $featureKey,
            'exception' => $exception?->getMessage(),
        ]);

        try {
            ExportTemplateAuditLog::query()->create([
                'template_version_id' => $versionId,
                'actor_id' => Auth::id(),
                'action' => ExportTemplateAuditLog::ACTION_FALLBACK,
                'metadata' => [
                    'feature_key' => $featureKey,
                    'output_format' => $format->value,
                    'reason' => $reason,
                    'exception' => $exception?->getMessage(),
                ],
            ]);
        } catch (\Throwable) {
            // Export cũ vẫn phải hoạt động nếu bảng audit chưa migrate.
        }
    }

    /**
     * Template Word tùy biến có thể vẫn còn lưới cố định 1-5/6-9. Không phát
     * hành file nếu một nhóm thực tế (đặc biệt 4-5) đã biến mất; nhánh catch
     * phía trên sẽ dùng exporter Word động để bảo toàn dữ liệu.
     *
     * @param  array<int|string>  $trainingScheduleIds
     */
    private function assertGroupedWordKeepsEveryPeriodRange(
        string $path,
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
    ): void {
        if (! is_file($path) || ! class_exists(\ZipArchive::class)) {
            return;
        }

        $details = ScheduleDetail::query()
            ->with(['subject', 'subjectLesson', 'instructor.unit', 'instructor.position', 'classroom.building'])
            ->whereIn('training_schedule_id', array_map('intval', $trainingScheduleIds))
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('subject_id')
            ->orderBy('training_schedule_id')
            ->orderBy('date')
            ->orderBy('period')
            ->get()
            ->groupBy(fn (ScheduleDetail $detail) => $detail->training_schedule_id);

        $archive = new \ZipArchive;
        if ($archive->open($path) !== true) {
            return;
        }
        $xml = (string) $archive->getFromName('word/document.xml');
        $archive->close();
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        foreach ($details as $scheduleDetails) {
            foreach ($this->scheduleGroupService->group($scheduleDetails) as $group) {
                $start = (int) $group['period_start'];
                $end = (int) $group['period_end'];
                $rangeFound = $start === $end
                    ? preg_match('/(?<!\d)'.preg_quote((string) $start, '/').'(?!\d)/u', $text) === 1
                    : preg_match('/(?<!\d)'.preg_quote((string) $start, '/').'\s*[-–÷]\s*'.preg_quote((string) $end, '/').'(?!\d)/u', $text) === 1;
                $subject = trim((string) ($group['subject_short_name'] ?: $group['subject_name']));
                $subjectFound = $subject === '' || mb_stripos($text, $subject, 0, 'UTF-8') !== false;

                if (! $rangeFound || ! $subjectFound) {
                    @unlink($path);
                    throw new \RuntimeException(
                        "Template Word Active làm mất nhóm tiết {$group['period_label']} {$subject}."
                    );
                }
            }
        }
    }

    /**
     * Excel: CLONE file biểu mẫu HK2 (giữ gạch chéo + chữ ký ảnh), chỉ đổ biến/lịch/màu.
     *
     * @param  array<int|string>  $trainingScheduleIds
     * @param  array<string, mixed>  $meta
     */
    public function exportTrainingCalendarExcel(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = [],
        ?string $layout = null,
    ): StreamedResponse {
        $layout ??= $this->layoutSelector->select($trainingScheduleIds, $startDate, $endDate);
        $payload = $this->buildTrainingPlanPayload($trainingScheduleIds, $startDate, $endDate, $meta);
        $filename = 'Lich_Huan_Luyen_'.$payload['start']->format('Ymd').'_'.$payload['end']->format('Ymd').'.xlsx';

        $export = $layout === LhlPeriodLayoutSelector::GROUPED_PERIODS
            ? new GroupedTrainingPlanFromTemplateExport($payload['classes'], $payload['meta'])
            : new TrainingPlanFromTemplateExport($payload['classes'], $payload['meta']);

        return $export->download($filename);
    }

    /**
     * Word lưới tuần + 3 chữ ký (preview editor meta).
     *
     * @param  array<int|string>  $trainingScheduleIds
     * @param  array<string, mixed>  $meta
     */
    public function exportTrainingCalendarWordGrid(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = [],
        ?string $layout = null,
    ): StreamedResponse {
        $layout ??= $this->layoutSelector->select($trainingScheduleIds, $startDate, $endDate);
        $payload = $this->buildTrainingPlanPayload($trainingScheduleIds, $startDate, $endDate, $meta);
        $filename = 'Lich_Huan_Luyen_'.$payload['start']->format('Ymd').'_'.$payload['end']->format('Ymd').'.docx';

        return (new TrainingPlanWordExport(
            $payload['classes'],
            $payload['meta'],
            $layout
        ))->download($filename);
    }

    /**
     * @param  array<int|string>  $trainingScheduleIds
     * @param  array<string, mixed>  $meta
     * @return array{start:Carbon,end:Carbon,meta:array<string,mixed>,classes:list<array<string,mixed>>}
     */
    protected function buildTrainingPlanPayload(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = [],
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $schedules = TrainingSchedule::query()
            ->with(['classModel', 'class', 'specialization'])
            ->whereIn('id', $trainingScheduleIds)
            ->get();

        $details = ScheduleDetail::query()
            ->with([
                'subject',
                'subjectLesson',
                'instructor.unit',
                'instructor.position',
                'classroom.building',
            ])
            ->whereIn('training_schedule_id', $trainingScheduleIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('subject_id')
            ->orderBy('date')
            ->orderBy('period')
            ->get()
            ->groupBy('training_schedule_id');

        $meta = $this->normalizeLhlMeta($meta, $schedules->first());

        $classes = [];
        $usedTitles = [];

        foreach ($schedules as $ts) {
            $className = $ts->class->name
                ?? $ts->classModel->name
                ?? $ts->class_code
                ?? ('Lịch #'.$ts->id);
            $sheetTitle = $this->uniqueSheetTitle(
                $ts->class->code
                    ?? $ts->classModel->code
                    ?? $ts->class_code
                    ?? $className
                    ?? ('TS'.$ts->id),
                $usedTitles
            );

            $tsDetails = $details->get($ts->id, collect());
            $semesterLabel = $this->formatSemesterLabel($ts->semester);
            $academicYear = (string) ($ts->academic_year ?: $start->format('Y').'-'.$end->format('Y'));

            $classes[] = [
                'sheet_title' => $sheetTitle,
                'class_name' => $className,
                'semester_label' => $semesterLabel,
                'academic_year' => $academicYear,
                'start' => $start,
                'end' => $end,
                'cells' => $this->cellsFromDetails($tsDetails),
                'legend' => $this->legendFromDetails($tsDetails),
            ];
        }

        return [
            'start' => $start,
            'end' => $end,
            'meta' => $meta,
            'classes' => $classes,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function normalizeLhlMeta(array $meta, $firstSchedule = null): array
    {
        $cfg = config('lhl_export', []);
        $signers = app(DigitalSignatureService::class)->resolveExportSigners($meta);

        $unitName = (string) ($meta['unit_name'] ?? '');
        if ($unitName === '' && $firstSchedule) {
            $unitName = (string) ($firstSchedule->specialization->name ?? '');
        }

        $semesterLine = (string) ($meta['semester_line'] ?? '');
        if ($semesterLine === '' && $firstSchedule) {
            $semesterLine = trim(
                $this->formatSemesterLabel($firstSchedule->semester)
                .' năm học '
                .(string) ($firstSchedule->academic_year ?? '')
            );
        }

        return [
            'org_left' => (string) ($meta['org_left'] ?? $meta['header_left'] ?? ($cfg['org_left'] ?? '')),
            'title' => (string) ($meta['title'] ?? ($cfg['title'] ?? 'LỊCH HUẤN LUYỆN')),
            'semester_line' => $semesterLine,
            'respect_line' => (string) ($meta['respect_line'] ?? ($cfg['respect_line'] ?? '')),
            'unit_name' => $unitName,
            'class_size' => (string) ($meta['class_size'] ?? ''),
            'groups' => (string) ($meta['groups'] ?? ''),
            'class_leader' => (string) ($meta['class_leader'] ?? ''),
            'classroom' => (string) ($meta['classroom'] ?? ''),
            'note' => (string) ($meta['note'] ?? ($cfg['note'] ?? '')),
            'common_notes' => (array) ($meta['common_notes'] ?? ($cfg['common_notes'] ?? [])),
            'common_notes_title' => (string) ($meta['common_notes_title'] ?? ($cfg['common_notes_title'] ?? 'KÍ HIỆU CHUNG')),
            'print_date_line' => (string) ($meta['print_date_line'] ?? ('Ngày in lịch: '.now()->format('d/m/Y'))),
            'date_line' => (string) ($meta['date_line'] ?? ('Ngày     tháng      năm '.now()->format('Y'))),
            'signers' => $signers,
            'header_left' => (string) ($meta['header_left'] ?? ''),
            'header_right' => (string) ($meta['header_right'] ?? ''),
            'footer_left' => (string) ($meta['footer_left'] ?? ''),
            'footer_right' => (string) ($meta['footer_right'] ?? ''),
        ];
    }

    /**
     * Xuất lịch huấn luyện (PDOT) — Word (bảng phẳng, fallback).
     *
     * @param  array<string, string|null>  $meta
     */
    public function exportTrainingCalendarWord(
        array $trainingScheduleIds,
        string $startDate,
        string $endDate,
        array $meta = []
    ): StreamedResponse {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $schedules = TrainingSchedule::query()
            ->with(['classModel', 'specialization'])
            ->whereIn('id', $trainingScheduleIds)
            ->get();

        $details = ScheduleDetail::query()
            ->with(['subject', 'classroom'])
            ->whereIn('training_schedule_id', $trainingScheduleIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('subject_id')
            ->orderBy('date')
            ->orderBy('period')
            ->get()
            ->groupBy('training_schedule_id');

        $meta = array_merge([
            'title' => 'LỊCH HUẤN LUYỆN',
        ], $meta);

        return WordExportTemplate::download(
            'Lich_Huan_Luyen_'.$start->format('Ymd').'_'.$end->format('Ymd').'.docx',
            $meta,
            function ($section) use ($schedules, $details, $start, $end) {
                $section->addText(
                    'Từ '.$start->format('d/m/Y').' đến '.$end->format('d/m/Y'),
                    ['italic' => true, 'size' => 10],
                    ['alignment' => Jc::CENTER]
                );
                $section->addTextBreak(1);

                $headers = ['Ngày', 'Thứ', 'Buổi', 'Môn (viết tắt)', 'Tiết', 'Phòng'];

                foreach ($schedules as $ts) {
                    $className = $ts->classModel->name ?? $ts->class_code ?? ('Lịch #'.$ts->id);
                    $section->addText('Lớp: '.$className, ['bold' => true, 'size' => 12], ['spaceAfter' => 120]);

                    $rows = [];
                    $weekdayMap = [1 => 'Hai', 2 => 'Ba', 3 => 'Tư', 4 => 'Năm', 5 => 'Sáu', 6 => 'Bảy', 7 => 'CN'];
                    foreach ($details->get($ts->id, collect()) as $d) {
                        $date = Carbon::parse($d->date);
                        $period = (int) $d->period;
                        $session = $period <= 5 ? 'Sáng (1–5)' : 'Chiều (6–9)';
                        $subject = $d->subject;
                        $rows[] = [
                            $date->format('d/m/Y'),
                            $weekdayMap[$date->dayOfWeekIso] ?? '',
                            $session,
                            $subject?->short_label ?? ($subject->name ?? ''),
                            (string) $period,
                            $d->classroom->name ?? '',
                        ];
                    }

                    if ($rows === []) {
                        $section->addText('(Chưa có tiết trong khoảng ngày)', ['italic' => true, 'size' => 10]);
                    } else {
                        WordExportTemplate::addSimpleTable($section, $headers, $rows);
                    }
                    $section->addTextBreak(1);
                }
            },
            true
        );
    }

    /**
     * @param  Collection<int, ScheduleDetail>  $details
     * @return Collection<int, object{date:string,period:int,period_end:int,period_label:string,subject_label:string,label:string,subject_id:?int}>
     */
    protected function cellsFromDetails(Collection $details): Collection
    {
        $subjectIds = $details->keyBy(fn ($detail) => implode('|', [
            Carbon::parse($detail->date)->toDateString(),
            (int) $detail->period,
        ]));

        return collect($this->scheduleGroupService->group($details))->map(function (array $group) use ($subjectIds) {
            $subjectLabel = trim($group['subject_short_name'] ?: $group['subject_name']);
            if ($group['lesson_type'] === 'practice' && $subjectLabel !== '') {
                $subjectLabel .= ' (TH)';
            }
            $label = $subjectLabel !== ''
                ? $group['period_label']."\n".$subjectLabel
                : '';
            $source = $subjectIds->get($group['date'].'|'.$group['period_start']);

            return (object) [
                'date' => $group['date'],
                'period' => $group['period_start'],
                'period_end' => $group['period_end'],
                'period_label' => $group['period_label'],
                'subject_label' => $subjectLabel,
                'label' => $label,
                'subject_id' => $source?->subject_id ? (int) $source->subject_id : null,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, ScheduleDetail>  $details
     * @return Collection<int, object{id:int,code:string,name:string,faculty:string,credits:int,theory:int,practice:int,exam:int}>
     */
    protected function legendFromDetails(Collection $details): Collection
    {
        $seen = [];
        $rows = collect();

        foreach ($details as $d) {
            $subject = $d->subject;
            if (! $subject || isset($seen[$subject->id])) {
                continue;
            }
            $seen[$subject->id] = true;
            $rows->push((object) [
                'id' => (int) $subject->id,
                'code' => $subject->display_code ?: (string) $subject->code,
                'name' => (string) $subject->name,
                'faculty' => (string) ($subject->faculty_code ?? ''),
                'credits' => (int) ($subject->credits ?? 0),
                // Cột "Tổng số" trên biểu mẫu = tổng tiết của môn; ưu tiên giá
                // trị môn khai báo, thiếu thì cộng LT + TH + Thi.
                'total' => (int) ($subject->total_hours
                    ?: ((int) ($subject->theory_hours ?? 0)
                        + (int) ($subject->practice_hours ?? 0)
                        + (int) ($subject->exam_hours ?? 0))),
                'theory' => (int) ($subject->theory_hours ?? 0),
                'practice' => (int) ($subject->practice_hours ?? 0),
                'exam' => (int) ($subject->exam_hours ?? 0),
            ]);
        }

        return $rows->values();
    }

    protected function formatSemesterLabel(?string $semester): string
    {
        $raw = strtolower(trim((string) $semester));

        return match (true) {
            in_array($raw, ['1', 'semester_1', 'hk1', 'hoc_ky_1', 'học kỳ 1'], true) => 'Học kỳ 1',
            in_array($raw, ['2', 'semester_2', 'hk2', 'hoc_ky_2', 'học kỳ 2'], true) => 'Học kỳ 2',
            in_array($raw, ['3', 'semester_3', 'hk3', 'he', 'summer'], true) => 'Học kỳ hè',
            $raw !== '' => (string) $semester,
            default => '',
        };
    }

    /**
     * @param  array<string, true>  $usedTitles
     */
    protected function uniqueSheetTitle(string $base, array &$usedTitles): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]]/', '', $base) ?: 'LHL';
        $title = mb_substr($title, 0, 31);
        if (! isset($usedTitles[$title])) {
            $usedTitles[$title] = true;

            return $title;
        }

        $i = 2;
        while ($i < 100) {
            $suffix = '_'.$i;
            $candidate = mb_substr($title, 0, 31 - mb_strlen($suffix)).$suffix;
            if (! isset($usedTitles[$candidate])) {
                $usedTitles[$candidate] = true;

                return $candidate;
            }
            $i++;
        }

        return mb_substr($title.'_'.uniqid(), 0, 31);
    }

    /**
     * Xuất kế hoạch huấn luyện cấp Khoa theo lớp (Word + meta header/footer).
     *
     * @param  array<string, string|null>  $meta
     */
    public function exportFacultyPlan(
        int $classId,
        ?string $startDate = null,
        ?string $endDate = null,
        array $meta = [],
    ): StreamedResponse {
        $class = ClassModel::query()->with('specialization')->findOrFail($classId);

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();

        $scheduleIds = TrainingSchedule::query()
            ->where(function ($q) use ($class) {
                $q->where('class_id', $class->id)
                    ->orWhere('class_code', $class->code);
            })
            ->pluck('id');

        $details = ScheduleDetail::query()
            ->with(['subject', 'subjectLesson', 'instructor', 'classroom'])
            ->whereIn('training_schedule_id', $scheduleIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('subject_id')
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        $user = Auth::user();
        if (TrainingDept::isFacultyManager($user)) {
            $ownedSubjectIds = TrainingDept::facultySubjectIds($user) ?? [];
            $unitId = (int) ($user->unit_id ?? 0);
            $details = $details->filter(function ($d) use ($ownedSubjectIds, $unitId) {
                // Chỉ tiết môn thuộc khoa
                if ($ownedSubjectIds !== [] && ! in_array((int) $d->subject_id, $ownedSubjectIds, true)) {
                    return false;
                }
                if (! $d->instructor_id) {
                    return true;
                }

                return ! $unitId || (int) ($d->instructor->unit_id ?? 0) === $unitId;
            })->values();
        }

        $planRows = FacultyTrainingPlanExport::rowsFromDetails($details);
        $unitName = $user?->unit?->name
            ?? ($class->specialization->name ?? 'Khoa');
        $className = $class->name ?? $class->code ?? (string) $classId;

        $title = $meta['title'] ?? null;
        if (! $title) {
            $title = 'LỊCH HUẤN LUYỆN THÁNG '.$start->format('m').' NĂM '.$start->format('Y')
                ."\nLỚP: ".$className.($unitName ? ' – '.$unitName : '');
        }

        $meta = array_merge([
            'title' => $title,
            'footer_left' => $meta['footer_left'] ?? '',
            'footer_right' => $meta['footer_right'] ?? '',
        ], $meta);

        $headers = ['Ngày', 'Thứ', 'Tiết', 'Địa điểm', 'Môn học', 'Tên bài', 'Giảng viên', 'Ghi chú'];
        $tableRows = $planRows->map(fn ($r) => [
            $r->day,
            $r->weekday,
            $r->period_label,
            $r->classroom,
            $r->subject_short,
            $r->lesson_name,
            $r->instructor,
            $r->note ?? '',
        ])->all();

        $filename = 'Ke_hoach_HL_'.$class->code.'_'.$start->format('Ymd').'_'.$end->format('Ymd').'.docx';

        return WordExportTemplate::download(
            $filename,
            $meta,
            function ($section) use ($headers, $tableRows) {
                WordExportTemplate::addSimpleTable($section, $headers, $tableRows);
            },
            true
        );
    }
}
