<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Models\YearlyResult;

class AnnualDeclarationService
{
    public function __construct(
        private readonly CalculationService $calculationService,
        private readonly PeriodService $periodService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getFormData(int $instructorId, ?int $year = null): array
    {
        $year ??= $this->periodService->currentYear();
        $instructor = Instructor::with(['unit', 'objectType', 'position'])->findOrFail($instructorId);
        $declaration = YearlyResult::query()
            ->currentPeriod()
            ->with(['objectType', 'position'])
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->first();

        [$periodFromDate, $periodToDate] = $this->periodService->dateRange($year);

        return [
            'instructor' => $instructor,
            'declaration' => $declaration,
            'objectTypes' => ObjectType::active()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'description', 'standard_hours', 'research_hours']),
            'positions' => Position::active()
                ->orderBy('ratio_percent')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'ratio_percent', 'min_classroom_percent']),
            'defaultYear' => $year,
            'defaultPeriodLabel' => $this->periodService->label($year),
            'periodModeLabel' => $this->periodService->modeLabel(),
            'periodStartDate' => $periodFromDate,
            'periodEndDate' => $periodToDate,
            'defaultFromDate' => $declaration?->declaration_from_date?->toDateString() ?: $periodFromDate,
            'defaultToDate' => $declaration?->declaration_to_date?->toDateString() ?: $periodToDate,
            'scheduleSnapshot' => $declaration?->schedule_teaching_details ?? [
                'total_hours' => 0,
                'total_periods' => 0,
                'rows' => [],
                'by_subject' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSchedule(int $instructorId, string $fromDate, string $toDate): array
    {
        return array_merge(
            $this->calculationService->retrieveTeachingSchedule($instructorId, $fromDate, $toDate),
            $this->calculationService->retrievePeriodSourceHours($instructorId, $fromDate, $toDate),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(int $instructorId, array $data): YearlyResult
    {
        $instructor = Instructor::with(['unit'])->findOrFail($instructorId);
        $objectType = ObjectType::active()->find($data['object_type_id']);
        $position = Position::active()->find($data['position_id']);
        if (! $objectType || ! $position) {
            throw new \RuntimeException('Đối tượng hoặc chức danh đã ngừng sử dụng. Vui lòng chọn lại danh mục đang hoạt động.');
        }
        if (! $this->periodService->rangeBelongsToPeriod(
            $data['declaration_from_date'],
            $data['declaration_to_date']
        )) {
            throw new \RuntimeException(
                'Khoảng kê khai phải nằm trong cùng một '
                    .mb_strtolower($this->periodService->modeLabel()).'.'
            );
        }
        $year = $this->periodService->resolveYearForDate($data['declaration_from_date']);

        $existing = YearlyResult::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->first();
        if ($existing?->isLocked()) {
            throw new \RuntimeException("Bảng giờ chuẩn năm {$year} đã khóa, không thể cập nhật.");
        }

        $data['object_type_id'] = $objectType->id;
        $data['position_id'] = $position->id;
        $calculated = $this->calculationService->buildPersonalDeclarationResult($instructor, $data);

        return DB::transaction(function () use ($instructorId, $year, $data, $calculated) {
            $result = YearlyResult::updateOrCreate(
                [
                    'instructor_id' => $instructorId,
                    'year' => $year,
                    'period_mode' => $this->periodService->mode(),
                ],
                array_merge($calculated, [
                    'other_teaching_notes' => $data['other_teaching_notes'] ?? null,
                    'status' => YearlyResult::STATUS_CALCULATED,
                    'declared_by' => Auth::id(),
                    'declared_at' => now(),
                    'declaration_status' => YearlyResult::DECLARATION_SUBMITTED,
                    'declaration_approved_by' => null,
                    'declaration_approved_at' => null,
                    'declaration_review_note' => null,
                    'calculated_by' => Auth::id(),
                    'calculated_at' => now(),
                    'locked_by' => null,
                    'locked_at' => null,
                ])
            );

            return $result->fresh([
                'instructor.unit',
                'objectType',
                'position',
                'declarer',
                'calculator',
            ]);
        });
    }

    public function review(YearlyResult $result, string $status, ?string $note = null): YearlyResult
    {
        if (! $result->declared_at) {
            throw new \RuntimeException('Bản ghi này không phải hồ sơ do giảng viên kê khai.');
        }
        if ($result->isLocked()) {
            throw new \RuntimeException('Hồ sơ đã khóa, không thể duyệt lại.');
        }

        ManagerUnitScope::ensureCanAccessInstructor($result->instructor_id);
        $result->update([
            'declaration_status' => $status,
            'declaration_approved_by' => Auth::id(),
            'declaration_approved_at' => now(),
            'declaration_review_note' => $note,
        ]);

        return $this->calculationService->rebuildDeclaration($result);
    }
}
