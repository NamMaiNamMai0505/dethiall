<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\StandardHours\Models\CalculationLog;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\InstructorNormReduction;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Support\InstructorScope;

class CalculationService
{
    public function __construct(
        private readonly HourNormService $hourNormService,
        private readonly PeriodService $periodService,
    ) {}

    public function paginateResults(array $filters = []): LengthAwarePaginator
    {
        $query = YearlyResult::with([
            'instructor.unit',
            'objectType',
            'position',
            'calculator',
        ])->currentPeriod();
        $this->applyManagerScopeToResults($query);

        $query->byYear($filters['year'] ?? null);
        $query->byInstructor($filters['instructor_id'] ?? null);
        $query->byUnit($filters['unit_id'] ?? null);
        $query->byStatus($filters['status'] ?? null);
        $query->byOverall($filters['overall_result'] ?? null);
        $query->search($filters['search'] ?? null);

        $sortBy = $filters['sort_by'] ?? 'total_standard_hours';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getFilterOptions(): array
    {
        return [
            'years' => $this->hourNormService->getYears(),
            'instructors' => InstructorScope::instructorsQuery()
                ->get(['id', 'name', 'code', 'unit_id']),
            'units' => ManagerUnitScope::unitOptions(),
            'statuses' => [
                YearlyResult::STATUS_CALCULATED => 'Đã tính',
                YearlyResult::STATUS_LOCKED => 'Đã khóa',
            ],
            'overallResults' => [
                'pass' => 'Đạt',
                'fail' => 'Không đạt',
            ],
        ];
    }

    public function getYearSummary(?string $year): array
    {
        if (blank($year)) {
            return [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'locked' => 0,
                'is_locked' => false,
            ];
        }

        $results = $this->scopedResultsQuery($year)->get();

        return [
            'total' => $results->count(),
            'passed' => $results->where('meets_overall', true)->count(),
            'failed' => $results->where('meets_overall', false)->count(),
            'locked' => $results->where('status', YearlyResult::STATUS_LOCKED)->count(),
            'is_locked' => $results->isNotEmpty() && $results->every(fn ($r) => $r->isLocked()),
        ];
    }

    /**
     * @return array{processed: int, skipped: int, previews: array<int, array<string, mixed>>, skipped_details: array<int, string>}
     */
    public function preview(string $year, ?int $unitId = null): array
    {
        $instructors = $this->getEligibleInstructors($unitId);
        $processed = 0;
        $skipped = 0;
        $previews = [];
        $skippedDetails = [];

        foreach ($instructors as $instructor) {
            try {
                $existing = YearlyResult::query()
                    ->currentPeriod()
                    ->where('instructor_id', $instructor->id)
                    ->where('year', $year)
                    ->first();
                $previews[] = $this->buildInstructorResult($instructor, $year, false, $existing);
                $processed++;
            } catch (\RuntimeException $e) {
                $skipped++;
                $skippedDetails[$instructor->id] = $e->getMessage();
            }
        }

        $this->logAction($year, CalculationLog::ACTION_PREVIEW, $processed, $skipped, $skippedDetails);

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'previews' => $previews,
            'skipped_details' => $skippedDetails,
        ];
    }

    /**
     * @return array{processed: int, skipped: int, skipped_details: array<int, string>}
     */
    public function calculate(string $year, ?int $unitId = null): array
    {
        if ($this->isYearLocked($year)) {
            throw new \RuntimeException('Năm đã được khóa, không thể tính lại.');
        }

        $instructors = $this->getEligibleInstructors($unitId);
        $processed = 0;
        $skipped = 0;
        $skippedDetails = [];

        DB::transaction(function () use ($instructors, $year, &$processed, &$skipped, &$skippedDetails) {
            foreach ($instructors as $instructor) {
                try {
                    $existing = YearlyResult::query()
                        ->currentPeriod()
                        ->where('instructor_id', $instructor->id)
                        ->where('year', $year)
                        ->first();
                    $data = $this->buildInstructorResult($instructor, $year, true, $existing);

                    YearlyResult::updateOrCreate(
                        [
                            'instructor_id' => $instructor->id,
                            'year' => $year,
                            'period_mode' => $this->periodService->mode(),
                        ],
                        array_merge($data, [
                            'status' => YearlyResult::STATUS_CALCULATED,
                            'calculated_by' => Auth::id(),
                            'calculated_at' => now(),
                            'locked_by' => null,
                            'locked_at' => null,
                        ])
                    );

                    $processed++;
                } catch (\RuntimeException $e) {
                    $skipped++;
                    $skippedDetails[$instructor->id] = $e->getMessage();
                }
            }
        });

        $this->logAction($year, CalculationLog::ACTION_CALCULATE, $processed, $skipped, $skippedDetails);

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'skipped_details' => $skippedDetails,
        ];
    }

    public function rollback(string $year): int
    {
        if ($this->isYearLocked($year)) {
            throw new \RuntimeException('Năm đã được khóa, không thể hoàn tác.');
        }

        $deleted = $this->scopedResultsQuery($year)
            ->where('status', YearlyResult::STATUS_CALCULATED)
            // Hồ sơ do giảng viên đã kê khai là dữ liệu gốc hằng năm, không
            // được xóa khi quản lý hoàn tác một lần chạy tính hàng loạt.
            ->whereNull('declared_at')
            ->delete();

        $this->logAction($year, CalculationLog::ACTION_ROLLBACK, $deleted, 0);

        return $deleted;
    }

    public function lock(string $year): int
    {
        $count = $this->scopedResultsQuery($year)
            ->where('status', YearlyResult::STATUS_CALCULATED)
            ->update([
                'status' => YearlyResult::STATUS_LOCKED,
                'locked_by' => Auth::id(),
                'locked_at' => now(),
            ]);

        if ($count === 0) {
            throw new \RuntimeException('Không có kết quả tính giờ để khóa cho năm này.');
        }

        $this->logAction($year, CalculationLog::ACTION_LOCK, $count, 0);

        return $count;
    }

    public function getHistory(int $limit = 20): Collection
    {
        $query = CalculationLog::with('performer')->currentPeriod();
        if (ManagerUnitScope::isScoped()) {
            $query->where('performed_by', Auth::id());
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function isYearLocked(string $year): bool
    {
        $results = $this->scopedResultsQuery($year)->get();

        if ($results->isEmpty()) {
            return false;
        }

        return $results->every(fn (YearlyResult $result) => $result->isLocked());
    }

    private function getEligibleInstructors(?int $unitId): Collection
    {
        $query = Instructor::active()
            ->with(['unit', 'objectType', 'position'])
            ->orderBy('name');
        ManagerUnitScope::applyToInstructorQuery($query);

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        return $query->get();
    }

    private function scopedResultsQuery(string $year): Builder
    {
        $query = YearlyResult::query()
            ->currentPeriod()
            ->where('year', $year);
        $this->applyManagerScopeToResults($query);

        return $query;
    }

    private function applyManagerScopeToResults(Builder $query): void
    {
        if (! ManagerUnitScope::isScoped()) {
            return;
        }

        $unitIds = ManagerUnitScope::managedUnitIds();
        $query->whereHas('instructor', fn (Builder $instructorQuery) => $instructorQuery
            ->whereIn('unit_id', $unitIds));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInstructorResult(
        Instructor $instructor,
        string $year,
        bool $persistMeta,
        ?YearlyResult $declaration = null
    ): array {
        $objectTypeId = $declaration?->object_type_id ?: $instructor->object_type_id;
        $positionId = $declaration?->position_id ?: $instructor->position_id;

        if (! $objectTypeId || ! $positionId) {
            throw new \RuntimeException("GV {$instructor->code}: chưa cấu hình đối tượng/chức danh.");
        }

        $objectType = ObjectType::query()->find($objectTypeId);
        $position = Position::query()->find($positionId);
        if (! $objectType || ! $position) {
            throw new \RuntimeException("GV {$instructor->code}: đối tượng/chức danh không hợp lệ.");
        }

        // Định mức = giờ chuẩn Đối tượng × tỉ lệ Chức danh (VD: 380 × 10% = 38)
        $ratio = (float) ($position->ratio_percent ?? 100);
        $baseNorm = $objectType->standardHoursForRatio($ratio);
        if ($baseNorm <= 0 && (float) $objectType->standard_hours <= 0) {
            throw new \RuntimeException("GV {$instructor->code}: đối tượng «{$objectType->name}» chưa có định mức giờ chuẩn.");
        }

        [$defaultStartDate, $defaultEndDate] = $this->periodService->dateRange($year);
        $startDate = $declaration?->declaration_from_date?->toDateString() ?: $defaultStartDate;
        $endDate = $declaration?->declaration_to_date?->toDateString() ?: $defaultEndDate;
        $schedule = $this->retrieveTeachingSchedule($instructor->id, $startDate, $endDate);
        $scheduleTeachingHours = (float) $schedule['total_hours'];
        $otherTeachingHours = max(0, (float) ($declaration?->other_teaching_hours ?? 0));
        $approvedOtherTeachingHours = $declaration?->declaration_status === YearlyResult::DECLARATION_APPROVED
            ? $otherTeachingHours
            : 0.0;
        $teachingHours = round($scheduleTeachingHours + $approvedOtherTeachingHours, 2);
        $conversionHours = $this->calculateConversionHours($instructor->id, $year, $startDate, $endDate);
        $overtimeConversionHours = $this->calculateOvertimeEligibleConversionHours(
            $instructor->id,
            $year,
            $startDate,
            $endDate
        );
        $researchHours = $this->calculateResearchHours($instructor->id, $year, $startDate, $endDate);

        $totalStandardHours = round($teachingHours + $conversionHours, 2);
        $overtimeEligibleHours = round(
            $scheduleTeachingHours + $approvedOtherTeachingHours + $overtimeConversionHours,
            2
        );

        // Điều 11.3 — giảm trừ định mức theo nghỉ/đột xuất (tỷ lệ thời gian)
        $reductionPct = $this->sumReductionPercent($instructor->id, $year);
        $standardNormHours = round($baseNorm * (1 - $reductionPct / 100), 2);
        $standardDifference = round($totalStandardHours - $standardNormHours, 2);
        $minClassroomHours = round($standardNormHours * ((float) ($position->min_classroom_percent ?? 50) / 100), 2);

        $meetsStandard = $totalStandardHours >= $standardNormHours;
        $meetsClassroom = $teachingHours >= $minClassroomHours;

        $researchNormHours = (float) $objectType->research_hours;
        $researchDifference = round($researchHours - $researchNormHours, 2);
        $meetsResearch = $researchHours >= $researchNormHours;

        $meetsOverall = $meetsStandard && $meetsClassroom && $meetsResearch;

        $result = [
            'instructor_id' => $instructor->id,
            'instructor_name' => $instructor->name,
            'instructor_code' => $instructor->code,
            'unit_name' => $instructor->unit?->name,
            'year' => $year,
            'period_mode' => $this->periodService->mode(),
            'object_type_id' => $objectTypeId,
            'position_id' => $positionId,
            'object_type_name' => $objectType->name,
            'position_name' => $position->name,
            'declaration_from_date' => $startDate,
            'declaration_to_date' => $endDate,
            'schedule_teaching_hours' => $scheduleTeachingHours,
            'other_teaching_hours' => $otherTeachingHours,
            'other_teaching_notes' => $declaration?->other_teaching_notes,
            'teaching_hours' => $teachingHours,
            'conversion_hours' => $conversionHours,
            'research_hours' => $researchHours,
            'total_standard_hours' => $totalStandardHours,
            'overtime_eligible_hours' => $overtimeEligibleHours,
            'standard_norm_hours' => $standardNormHours,
            'standard_difference' => $standardDifference,
            'min_classroom_hours' => $minClassroomHours,
            'norm_reduction_percent' => $reductionPct,
            'meets_standard' => $meetsStandard,
            'meets_classroom' => $meetsClassroom,
            'research_norm_hours' => $researchNormHours,
            'research_difference' => $researchDifference,
            'meets_research' => $meetsResearch,
            'meets_overall' => $meetsOverall,
        ];

        if (! $persistMeta) {
            return $result;
        }

        $result['schedule_teaching_details'] = $schedule;
        $result['schedule_retrieved_at'] = now();

        return collect($result)->only([
            'instructor_id',
            'year',
            'period_mode',
            'object_type_id',
            'position_id',
            'declaration_from_date',
            'declaration_to_date',
            'schedule_teaching_hours',
            'other_teaching_hours',
            'other_teaching_notes',
            'schedule_teaching_details',
            'schedule_retrieved_at',
            'teaching_hours',
            'conversion_hours',
            'research_hours',
            'total_standard_hours',
            'overtime_eligible_hours',
            'standard_norm_hours',
            'standard_difference',
            'min_classroom_hours',
            'meets_standard',
            'meets_classroom',
            'research_norm_hours',
            'research_difference',
            'meets_research',
            'meets_overall',
        ])->toArray();
    }

    /**
     * Tính đầy đủ một hồ sơ do giảng viên tự kê khai trước khi lưu.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildPersonalDeclarationResult(Instructor $instructor, array $data): array
    {
        $year = (string) $this->periodService->resolveYearForDate($data['declaration_from_date']);
        $draft = new YearlyResult;
        $draft->forceFill([
            'object_type_id' => $data['object_type_id'],
            'position_id' => $data['position_id'],
            'declaration_from_date' => $data['declaration_from_date'],
            'declaration_to_date' => $data['declaration_to_date'],
            'other_teaching_hours' => $data['other_teaching_hours'] ?? 0,
            'other_teaching_notes' => $data['other_teaching_notes'] ?? null,
            'declaration_status' => YearlyResult::DECLARATION_SUBMITTED,
        ]);

        return $this->buildInstructorResult($instructor, $year, true, $draft);
    }

    /**
     * Truy xuất chi tiết các tiết lý thuyết/thực hành từ Lịch huấn luyện.
     *
     * @return array{total_hours: float, total_periods: int, rows: array<int, array<string, mixed>>, by_subject: array<int, array<string, mixed>>}
     */
    public function retrieveTeachingSchedule(int $instructorId, string $startDate, string $endDate): array
    {
        $from = Carbon::parse($startDate)->startOfDay();
        $to = Carbon::parse($endDate)->startOfDay();
        if ($to->lt($from)) {
            throw new \RuntimeException('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        }

        $details = ScheduleDetail::query()
            ->with([
                'subject:id,name,code,abbreviation',
                'trainingSchedule:id,name,code,class_id,class_code',
                'trainingSchedule.class:id,name,code',
                'classroom:id,name',
                'standardHourCategory:id,code,name,coefficient,fixed_hours,conversion_method',
            ])
            ->where('instructor_id', $instructorId)
            ->whereIn('lesson_type', ['theory', 'practice'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        $weekdays = [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật',
        ];

        $defaultCategory = ConversionCategory::query()
            ->active()
            ->where('entry_source', 'schedule')
            ->orderBy('code')
            ->first();

        $rows = $details->map(function (ScheduleDetail $detail) use ($weekdays, $defaultCategory) {
            $date = Carbon::parse($detail->date);
            $class = $detail->trainingSchedule?->class;
            $category = $detail->standardHourCategory ?: $defaultCategory;
            $hours = $category?->calculateHours(1) ?? 1.0;

            return [
                'id' => $detail->id,
                'date' => $date->toDateString(),
                'date_label' => $date->format('d/m/Y'),
                'weekday' => $weekdays[$date->isoWeekday()] ?? '',
                'period' => (int) $detail->period,
                'lesson_type' => $detail->lesson_type,
                'lesson_type_label' => $detail->lesson_type === 'theory' ? 'Lý thuyết' : 'Thực hành',
                'subject_code' => $detail->subject?->code,
                'subject_name' => $detail->subject?->name ?? 'Chưa xác định môn',
                'schedule_code' => $detail->trainingSchedule?->code,
                'schedule_name' => $detail->trainingSchedule?->name,
                'class_code' => $class?->code ?? $detail->trainingSchedule?->class_code,
                'class_name' => $class?->name,
                'classroom' => $detail->classroom?->name,
                'conversion_category_code' => $category?->code,
                'conversion_category_name' => $category?->name,
                'conversion_coefficient' => (float) ($category?->coefficient ?? 1),
                'hours' => $hours,
            ];
        })->values();

        $bySubject = $rows
            ->groupBy(fn (array $row) => ($row['subject_code'] ?: '—').'|'.$row['subject_name'])
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'subject_code' => $first['subject_code'],
                    'subject_name' => $first['subject_name'],
                    'theory_hours' => $group->where('lesson_type', 'theory')->sum('hours'),
                    'practice_hours' => $group->where('lesson_type', 'practice')->sum('hours'),
                    'total_hours' => $group->sum('hours'),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower((string) $row['subject_name']))
            ->values();

        return [
            'total_hours' => round((float) $rows->sum('hours'), 2),
            'total_periods' => $rows->count(),
            'rows' => $rows->all(),
            'by_subject' => $bySubject->all(),
        ];
    }

    /**
     * Các nguồn giờ đã duyệt trong khoảng tra cứu tự do.
     *
     * Không lọc theo cột year vì khoảng ngày có thể đi qua nhiều năm/năm học.
     * Kỳ chính thức chỉ được dùng khi lưu/chốt YearlyResult.
     *
     * @return array{conversion_hours: float, research_hours: float}
     */
    public function retrievePeriodSourceHours(int $instructorId, string $startDate, string $endDate): array
    {
        $conversionHours = (float) ConversionRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('is_external_invitation', false)
            ->where('status', ConversionRecord::STATUS_APPROVED)
            ->whereBetween('activity_date', [$startDate, $endDate])
            ->sum('converted_hours');

        $researchMemberHours = (float) ResearchRecordMember::query()
            ->where('instructor_id', $instructorId)
            ->whereHas('researchRecord', function ($query) use ($startDate, $endDate) {
                $query->currentPeriod()
                    ->where('status', ResearchRecord::STATUS_APPROVED)
                    ->whereBetween('acceptance_date', [$startDate, $endDate]);
            })
            ->sum('converted_hours');

        // Bản ghi cũ chưa có bảng thành viên vẫn phải được tính cho chính chủ.
        $legacyResearchHours = (float) ResearchRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members')
            ->whereBetween('acceptance_date', [$startDate, $endDate])
            ->sum('converted_hours');

        return [
            'conversion_hours' => max(0, round($conversionHours, 2)),
            'research_hours' => max(0, round($researchMemberHours + $legacyResearchHours, 2)),
        ];
    }

    private function calculateConversionHours(
        int $instructorId,
        string $year,
        ?string $startDate = null,
        ?string $endDate = null
    ): float {
        $query = ConversionRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->where('is_external_invitation', false)
            ->where('status', ConversionRecord::STATUS_APPROVED);
        if ($startDate && $endDate) {
            $query->whereBetween('activity_date', [$startDate, $endDate]);
        }
        $base = (float) $query->sum('converted_hours');

        $exchange = $this->periodService->isFullRange($year, $startDate, $endDate)
            ? app(HourExchangeService::class)->netConversionAdjustment($instructorId, $year)
            : 0;

        return max(0, round($base + $exchange, 2));
    }

    private function calculateResearchHours(
        int $instructorId,
        string $year,
        ?string $startDate = null,
        ?string $endDate = null
    ): float {
        $memberQuery = ResearchRecordMember::query()
            ->where('instructor_id', $instructorId)
            ->whereHas('researchRecord', function ($query) use ($year, $startDate, $endDate) {
                $query->currentPeriod()
                    ->where('year', $year)
                    ->where('status', ResearchRecord::STATUS_APPROVED);
                if ($startDate && $endDate) {
                    $query->whereBetween('acceptance_date', [$startDate, $endDate]);
                }
            });
        $fromMembers = (float) $memberQuery->sum('converted_hours');

        $base = $fromMembers > 0
            ? $fromMembers
            : (float) ResearchRecord::query()
                ->currentPeriod()
                ->where('instructor_id', $instructorId)
                ->where('year', $year)
                ->where('status', ResearchRecord::STATUS_APPROVED)
                ->whereDoesntHave('members')
                ->when(
                    $startDate && $endDate,
                    fn ($query) => $query->whereBetween('acceptance_date', [$startDate, $endDate])
                )
                ->sum('converted_hours');

        $exchange = $this->periodService->isFullRange($year, $startDate, $endDate)
            ? app(HourExchangeService::class)->netResearchAdjustment($instructorId, $year)
            : 0;

        return max(0, round($base + $exchange, 2));
    }

    /**
     * Public helper cho DepartmentOvertimeService (fallback khi chưa có YearlyResult).
     *
     * @return array<string, mixed>
     */
    public function previewMemberForDepartment(Instructor $instructor, string $year): array
    {
        $existing = YearlyResult::query()
            ->currentPeriod()
            ->where('instructor_id', $instructor->id)
            ->where('year', $year)
            ->first();

        return $this->buildInstructorResult($instructor, $year, false, $existing);
    }

    /** Tổng % giảm trừ Đ.11.3 (capped 100). */
    public function sumReductionPercent(int $instructorId, string $year): float
    {
        if (! Schema::hasTable('instructor_norm_reductions')) {
            return 0.0;
        }

        $sum = 0.0;
        $rows = InstructorNormReduction::query()
            ->active()
            ->currentPeriod()
            ->forYear($year)
            ->where('instructor_id', $instructorId)
            ->get();
        foreach ($rows as $row) {
            $sum += $row->resolvedPercent();
        }

        return max(0, min(100, round($sum, 2)));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function logAction(
        string $year,
        string $action,
        int $processed,
        int $skipped = 0,
        array $skippedDetails = []
    ): void {
        CalculationLog::create([
            'year' => $year,
            'period_mode' => $this->periodService->mode(),
            'action' => $action,
            'instructors_processed' => $processed,
            'instructors_skipped' => $skipped,
            'notes' => empty($skippedDetails) ? null : json_encode($skippedDetails, JSON_UNESCAPED_UNICODE),
            'performed_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }

    private function calculateOvertimeEligibleConversionHours(
        int $instructorId,
        string $year,
        ?string $startDate = null,
        ?string $endDate = null
    ): float {
        return round((float) ConversionRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->where('status', ConversionRecord::STATUS_APPROVED)
            ->where('is_external_invitation', false)
            ->where('has_other_remuneration', false)
            ->when(
                $startDate && $endDate,
                fn ($query) => $query->whereBetween('activity_date', [$startDate, $endDate])
            )
            ->sum('converted_hours'), 2);
    }

    public function rebuildDeclaration(YearlyResult $declaration): YearlyResult
    {
        $instructor = Instructor::with(['unit', 'objectType', 'position'])
            ->findOrFail($declaration->instructor_id);
        $data = $this->buildInstructorResult(
            $instructor,
            (string) $declaration->year,
            true,
            $declaration
        );

        $declaration->update(array_merge($data, [
            'calculated_by' => Auth::id(),
            'calculated_at' => now(),
        ]));

        return $declaration->fresh();
    }
}
