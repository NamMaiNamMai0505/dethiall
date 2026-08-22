<?php

namespace Modules\StandardHours\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\AcademicDepartment;
use Modules\StandardHours\Models\DepartmentOvertimeAllocation;
use Modules\StandardHours\Models\DepartmentOvertimePool;
use Modules\StandardHours\Models\InstructorNormReduction;
use Modules\StandardHours\Models\YearlyResult;

/**
 * Điều 17 TT 06/2026 — vượt định mức tính theo bộ môn, phân bổ dân chủ.
 */
class DepartmentOvertimeService
{
    public function __construct(
        private readonly CalculationService $calculationService,
        private readonly HourNormService $hourNormService,
        private readonly PeriodService $periodService,
    ) {}

    /**
     * Tính pool vượt cho 1 bộ môn / năm.
     * Ưu tiên lấy YearlyResult đã tính; fallback tính tạm từ CalculationService.
     */
    public function calculatePool(AcademicDepartment $department, string $year, bool $persist = true): DepartmentOvertimePool
    {
        $members = Instructor::active()
            ->where('department_id', $department->id)
            ->with(['objectType', 'position', 'unit'])
            ->orderBy('name')
            ->get();

        if ($members->isEmpty()) {
            throw new \RuntimeException('Bộ môn chưa có giảng viên. Hãy gán GV trước.');
        }

        $snapshot = [];
        $poolMust = 0.0;
        $poolDone = 0.0;

        foreach ($members as $instructor) {
            $row = $this->memberHours($instructor, $year);
            $snapshot[] = $row;
            $poolMust += $row['must_hours'];
            $poolDone += $row['done_hours'];
        }

        $excess = max(0, round($poolDone - $poolMust, 2));

        $payload = [
            'pool_must_hours' => round($poolMust, 2),
            'pool_done_hours' => round($poolDone, 2),
            'pool_excess_hours' => $excess,
            'member_count' => $members->count(),
            'member_snapshot' => $snapshot,
            'status' => DepartmentOvertimePool::STATUS_DRAFT,
            'calculated_by' => Auth::id(),
            'calculated_at' => now(),
            'locked_by' => null,
            'locked_at' => null,
        ];

        if (! $persist) {
            $pool = new DepartmentOvertimePool(array_merge($payload, [
                'department_id' => $department->id,
                'year' => $year,
                'period_mode' => $this->periodService->mode(),
            ]));
            $pool->setRelation('department', $department);

            return $pool;
        }

        return DepartmentOvertimePool::query()->updateOrCreate(
            [
                'department_id' => $department->id,
                'year' => $year,
                'period_mode' => $this->periodService->mode(),
            ],
            $payload
        );
    }

    /**
     * @return array{instructor_id:int,name:string,code:string,must_hours:float,done_hours:float,excess_hours:float,reduction_percent:float}
     */
    public function memberHours(Instructor $instructor, string $year): array
    {
        $yearly = YearlyResult::query()
            ->currentPeriod()
            ->where('instructor_id', $instructor->id)
            ->where('year', $year)
            ->first();

        if ($yearly) {
            $must = (float) $yearly->standard_norm_hours;
            $done = (float) $yearly->overtime_eligible_hours;
            $reductionPct = $this->totalReductionPercent($instructor->id, $year);

            return [
                'instructor_id' => $instructor->id,
                'name' => $instructor->name,
                'code' => $instructor->code,
                'must_hours' => $must,
                'done_hours' => $done,
                'excess_hours' => round($done - $must, 2),
                'reduction_percent' => $reductionPct,
                'source' => 'yearly_result',
            ];
        }

        // Fallback: đối tượng.standard_hours × chức danh.ratio%
        if (! $instructor->object_type_id || ! $instructor->position_id) {
            throw new \RuntimeException("GV {$instructor->code}: chưa cấu hình đối tượng/chức danh giờ chuẩn.");
        }

        $instructor->loadMissing(['objectType', 'position']);
        $objectType = $instructor->objectType;
        $position = $instructor->position;
        if (! $objectType || ! $position) {
            throw new \RuntimeException("GV {$instructor->code}: đối tượng/chức danh không hợp lệ.");
        }

        $baseMust = $objectType->standardHoursForRatio((float) ($position->ratio_percent ?? 100));
        $reductionPct = $this->totalReductionPercent($instructor->id, $year);
        $must = round($baseMust * (1 - $reductionPct / 100), 2);

        // Dùng reflection-safe: preview path
        try {
            $built = $this->calculationService->previewMemberForDepartment($instructor, $year);
            $done = (float) ($built['total_standard_hours'] ?? 0);
        } catch (\Throwable $e) {
            $done = 0.0;
        }

        return [
            'instructor_id' => $instructor->id,
            'name' => $instructor->name,
            'code' => $instructor->code,
            'must_hours' => $must,
            'done_hours' => $done,
            'excess_hours' => round($done - $must, 2),
            'reduction_percent' => $reductionPct,
            'source' => 'live',
        ];
    }

    public function totalReductionPercent(int $instructorId, string $year): float
    {
        $rows = InstructorNormReduction::query()
            ->active()
            ->currentPeriod()
            ->forYear($year)
            ->where('instructor_id', $instructorId)
            ->get();

        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += $row->resolvedPercent();
        }

        return max(0, min(100, round($sum, 2)));
    }

    /**
     * Lưu phân bổ: map instructor_id => hours.
     *
     * @param  array<int, float|string>  $allocations
     */
    public function saveAllocations(DepartmentOvertimePool $pool, array $allocations, ?string $note = null): DepartmentOvertimePool
    {
        if ($pool->isLocked()) {
            throw new \RuntimeException('Pool đã khóa, không sửa phân bổ.');
        }

        $allowedInstructorIds = collect($pool->member_snapshot ?? [])
            ->pluck('instructor_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $invalidInstructorIds = array_diff(
            array_map('intval', array_keys($allocations)),
            $allowedInstructorIds
        );
        if ($invalidInstructorIds !== []) {
            throw new \RuntimeException('Danh sách phân bổ chứa giảng viên không thuộc pool của bộ môn.');
        }

        $total = 0.0;
        foreach ($allocations as $hours) {
            $total += max(0, (float) $hours);
        }
        $total = round($total, 2);
        if ($total - $pool->pool_excess_hours > 0.05) {
            throw new \RuntimeException(
                "Tổng phân bổ ({$total}) vượt pool ({$pool->pool_excess_hours} giờ)."
            );
        }

        $memberLimits = collect($pool->member_snapshot ?? [])
            ->mapWithKeys(fn (array $row) => [
                (int) $row['instructor_id'] => max(0, (float) ($row['must_hours'] ?? 0)),
            ]);
        foreach ($allocations as $instructorId => $hours) {
            $limit = (float) ($memberLimits[(int) $instructorId] ?? 0);
            if ((float) $hours - $limit > 0.05) {
                throw new \RuntimeException(
                    'Giờ vượt phân bổ cho một giảng viên không được lớn hơn định mức cá nhân '
                        ."({$limit} giờ)."
                );
            }
        }

        DB::transaction(function () use ($pool, $allocations, $note) {
            $pool->allocations()->delete();
            foreach ($allocations as $instructorId => $hours) {
                $h = round(max(0, (float) $hours), 2);
                if ($h <= 0) {
                    continue;
                }
                DepartmentOvertimeAllocation::create([
                    'pool_id' => $pool->id,
                    'instructor_id' => (int) $instructorId,
                    'allocated_hours' => $h,
                ]);
            }
            $pool->update([
                'note' => $note ?? $pool->note,
                'status' => DepartmentOvertimePool::STATUS_FINALIZED,
            ]);
        });

        return $pool->fresh(['allocations.instructor', 'department']);
    }

    /** Gợi ý chia đều pool cho các GV có excess > 0, còn lại chia đều tất cả. */
    public function suggestEqual(DepartmentOvertimePool $pool): array
    {
        $snapshot = $pool->member_snapshot ?? [];
        if ($snapshot === []) {
            return [];
        }
        $ids = array_column($snapshot, 'instructor_id');
        $n = count($ids);
        if ($n === 0 || $pool->pool_excess_hours <= 0) {
            return array_fill_keys($ids, 0);
        }
        $each = round($pool->pool_excess_hours / $n, 2);
        $map = [];
        $acc = 0.0;
        foreach ($ids as $i => $id) {
            if ($i === $n - 1) {
                $map[$id] = round($pool->pool_excess_hours - $acc, 2);
            } else {
                $map[$id] = $each;
                $acc += $each;
            }
        }

        return $map;
    }

    public function lock(DepartmentOvertimePool $pool): DepartmentOvertimePool
    {
        $pool->update([
            'status' => DepartmentOvertimePool::STATUS_LOCKED,
            'locked_by' => Auth::id(),
            'locked_at' => now(),
        ]);

        return $pool->fresh();
    }

    public function years(): array
    {
        return $this->hourNormService->getYears();
    }
}
