<?php

namespace Modules\Grades\Services;

use Illuminate\Support\Facades\Auth;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Exceptions\ActiveTemplateNotFoundException;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Services\ActiveTemplateResolver;
use Modules\ExportTemplates\Services\TemplateRenderService;
use Modules\Grades\Models\GradeBook;
use Modules\Grades\Models\GradeCell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeExportService
{
    public function __construct(
        private readonly ActiveTemplateResolver $activeTemplateResolver,
        private readonly TemplateRenderService $templateRenderService,
    ) {}

    public function download(GradeBook $book, ?int $templateId = null, string $format = 'excel'): StreamedResponse|BinaryFileResponse
    {
        $book->loadMissing(['columns', 'classModel', 'subject', 'instructor']);
        $students = app(GradeBookService::class)->studentsForBook($book);
        $cells = GradeCell::query()
            ->where('grade_book_id', $book->id)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->keyBy('grade_column_id'));

        $outputFormat = in_array(strtolower($format), ['word', 'docx'], true)
            ? OutputFormat::WORD
            : OutputFormat::EXCEL;
        $active = $this->activeTemplateResolver->find('grades.score_sheet', $outputFormat);
        if ($active) {
            try {
                $path = $this->templateRenderService->renderActive(
                    'grades.score_sheet', $outputFormat,
                    ['grade_book_id' => $book->id, 'generated_by' => Auth::user()?->name ?? 'Hệ thống'],
                    Auth::id()
                );
                $extension = $outputFormat === OutputFormat::WORD ? 'docx' : 'xlsx';

                return response()->download($path, 'bang-diem-'.$book->id.'-'.now()->format('Ymd-His').'.'.$extension, [
                    'X-Template-Engine' => 'active',
                    'X-Template-Version' => (string) $active->version_number,
                ])->deleteFileAfterSend(true);
            } catch (\Throwable $exception) {
                report($exception);
                ExportTemplateAuditLog::query()->create([
                    'template_id' => $active->template_id,
                    'template_version_id' => $active->id,
                    'actor_id' => Auth::id(),
                    'action' => ExportTemplateAuditLog::ACTION_FALLBACK,
                    'metadata' => ['feature_key' => 'grades.score_sheet', 'output_format' => $outputFormat->value, 'reason' => 'render_failed', 'exception' => $exception->getMessage()],
                ]);
            }
        }

        if ($outputFormat === OutputFormat::WORD) {
            throw new ActiveTemplateNotFoundException('Chưa có template Word Active cho bảng điểm.');
        }

        $spreadsheet = $this->buildDefaultSheet($book, $students, $cells);
        $filename = 'bang-diem-'.$book->id.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'X-Template-Engine' => 'legacy-fallback',
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function buildDefaultSheet(GradeBook $book, $students, $cells): Spreadsheet
    {
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Bang diem');
        $sheet->setCellValue('A1', $book->title);
        $sheet->setCellValue('A2', 'Lớp: '.($book->classModel?->name ?? ''));
        $sheet->setCellValue('B2', 'Môn: '.($book->subject?->name ?? ''));
        $sheet->setCellValue('C2', 'Năm học: '.($book->academic_year ?? ''));

        $headers = ['STT', 'Mã', 'Họ tên'];
        foreach ($book->columns as $col) {
            $headers[] = $col->name;
        }
        $colIdx = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($colIdx++, 5, $h);
        }

        $row = 6;
        $stt = 1;
        foreach ($students as $stu) {
            $sheet->setCellValueByColumnAndRow(1, $row, $stt++);
            $sheet->setCellValueByColumnAndRow(2, $row, $stu->code);
            $sheet->setCellValueByColumnAndRow(3, $row, $stu->name);
            $c = 4;
            foreach ($book->columns as $col) {
                $score = $cells[$stu->id][$col->id]->score ?? null;
                $sheet->setCellValueByColumnAndRow($c++, $row, $score);
            }
            $row++;
        }

        return $ss;
    }
}
