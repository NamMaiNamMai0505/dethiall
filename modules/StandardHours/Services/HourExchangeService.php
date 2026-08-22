<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\HourExchangeRecord;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Support\InstructorScope;
use Modules\Unit\Models\Unit;

class HourExchangeService
{
    /** TT nội bộ: 1 giờ chuẩn = 3 giờ hành chính NCKH. */
    public const NCKH_TO_CM_RATE = 1 / 3;

    public const CM_TO_NCKH_RATE = 3.0;

    public function __construct(
        private readonly HourNormService $hourNormService,
        private readonly PeriodService $periodService,
    ) {}

    public function getYears(): array
    {
        return $this->hourNormService->getYears();
    }

    public function getUnits()
    {
        $query = Unit::query()->orderBy('name');
        if (ManagerUnitScope::isScoped()) {
            $query->whereIn('id', ManagerUnitScope::managedUnitIds());
        }

        return $query->get(['id', 'name', 'code']);
    }

    public function getInstructors(?int $unitId = null)
    {
        $query = Instructor::query()
            ->with('unit:id,name,code')
            ->orderBy('name');

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        if ($scoped = InstructorScope::instructorId()) {
            $query->where('id', $scoped);
        } else {
            ManagerUnitScope::applyToInstructorQuery($query);
        }

        return $query->get(['id', 'name', 'code', 'unit_id']);
    }

    /**
     * @return array{
     *   research_hours: float,
     *   conversion_hours: float,
     *   research_base: float,
     *   conversion_base: float,
     *   exchange_nckh_to_cm_source: float,
     *   exchange_nckh_to_cm_target: float,
     *   exchange_cm_to_nckh_source: float,
     *   exchange_cm_to_nckh_target: float,
     *   rate: float
     * }
     */
    public function getBalances(int $instructorId, string $year): array
    {
        $researchBase = $this->baseResearchHours($instructorId, $year);
        $conversionBase = $this->baseConversionHours($instructorId, $year);

        $nckhToCmSource = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_NCKH_TO_CM, 'source_hours');
        $nckhToCmTarget = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_NCKH_TO_CM, 'target_hours');
        $cmToNckhSource = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_CM_TO_NCKH, 'source_hours');
        $cmToNckhTarget = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_CM_TO_NCKH, 'target_hours');

        $researchHours = round($researchBase - $nckhToCmSource + $cmToNckhTarget, 2);
        $conversionHours = round($conversionBase - $cmToNckhSource + $nckhToCmTarget, 2);

        return [
            'research_hours' => max(0, $researchHours),
            'conversion_hours' => max(0, $conversionHours),
            'research_base' => $researchBase,
            'conversion_base' => $conversionBase,
            'exchange_nckh_to_cm_source' => $nckhToCmSource,
            'exchange_nckh_to_cm_target' => $nckhToCmTarget,
            'exchange_cm_to_nckh_source' => $cmToNckhSource,
            'exchange_cm_to_nckh_target' => $cmToNckhTarget,
            'rate' => self::NCKH_TO_CM_RATE,
        ];
    }

    public function preview(string $direction, float $sourceHours, float $targetHours, ?float $rate = null): array
    {
        $rate = $this->rateForDirection($direction);

        if ($direction === HourExchangeRecord::DIRECTION_NCKH_TO_CM) {
            // 1 giờ NCKH = rate giờ HĐ CM → hao hụt NCKH = target / rate
            $requiredSource = round($targetHours / $rate, 2);
            $projectedTarget = round($sourceHours * $rate, 2);

            return [
                'direction' => $direction,
                'rate' => $rate,
                'source_hours' => $sourceHours,
                'target_hours' => $targetHours,
                'required_source_hours' => $requiredSource,
                'projected_target_hours' => $projectedTarget,
                'consume_label' => 'Hao hụt giờ NCKH',
                'receive_label' => 'Nhận giờ HĐ CM',
                'consume_hours' => $requiredSource > 0 ? $requiredSource : $sourceHours,
                'receive_hours' => $targetHours > 0 ? $targetHours : $projectedTarget,
            ];
        }

        // CM → NCKH: 1 giờ HĐ CM = rate giờ NCKH
        $requiredSource = round($targetHours / $rate, 2);
        $projectedTarget = round($sourceHours * $rate, 2);

        return [
            'direction' => $direction,
            'rate' => $rate,
            'source_hours' => $sourceHours,
            'target_hours' => $targetHours,
            'required_source_hours' => $requiredSource,
            'projected_target_hours' => $projectedTarget,
            'consume_label' => 'Hao hụt giờ HĐ CM',
            'receive_label' => 'Nhận giờ NCKH',
            'consume_hours' => $requiredSource > 0 ? $requiredSource : $sourceHours,
            'receive_hours' => $targetHours > 0 ? $targetHours : $projectedTarget,
        ];
    }

    public function exchange(array $data): HourExchangeRecord
    {
        $instructorId = (int) $data['instructor_id'];
        $year = $data['year'];
        $direction = $data['direction'];
        $rate = $this->rateForDirection($direction);

        $sourceHours = round((float) $data['source_hours'], 2);
        $targetHours = round((float) $data['target_hours'], 2);

        if ($sourceHours <= 0 || $targetHours <= 0) {
            throw new \RuntimeException('Số giờ quy đổi phải lớn hơn 0.');
        }
        if (blank($data['notes'] ?? null)) {
            throw new \RuntimeException('Phải ghi rõ căn cứ của quyết định bù giờ.');
        }

        // Enforce rate consistency (allow tiny float drift).
        $expectedTarget = round($sourceHours * $rate, 2);
        if (abs($expectedTarget - $targetHours) > 0.05) {
            // Prefer source as truth if user filled both inconsistently.
            $targetHours = $expectedTarget;
        }

        $balances = $this->getBalances($instructorId, $year);
        $yearlyResult = YearlyResult::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->first();
        if (! $yearlyResult) {
            throw new \RuntimeException(
                'Phải tính/kê khai bảng giờ chuẩn của giảng viên trước khi quyết định bù giờ.'
            );
        }

        if ($direction === HourExchangeRecord::DIRECTION_NCKH_TO_CM) {
            if ($sourceHours > $balances['research_hours'] + 0.001) {
                throw new \RuntimeException('Số giờ NCKH vượt quá số dư hiện có ('.$balances['research_hours'].' giờ).');
            }
            $targetDeficit = max(
                0,
                round(
                    (float) $yearlyResult->standard_norm_hours
                        - ((float) $yearlyResult->teaching_hours + $balances['conversion_hours']),
                    2
                )
            );
            if ($targetHours > $targetDeficit + 0.05) {
                throw new \RuntimeException(
                    "Chỉ được bù tối đa phần giờ chuẩn còn thiếu ({$targetDeficit} giờ)."
                );
            }
        } else {
            if ($sourceHours > $balances['conversion_hours'] + 0.001) {
                throw new \RuntimeException('Số giờ HĐ CM vượt quá số dư hiện có ('.$balances['conversion_hours'].' giờ).');
            }
            $targetDeficit = max(
                0,
                round((float) $yearlyResult->research_norm_hours - $balances['research_hours'], 2)
            );
            if ($targetHours > $targetDeficit + 0.05) {
                throw new \RuntimeException(
                    "Chỉ được bù tối đa phần định mức NCKH còn thiếu ({$targetDeficit} giờ)."
                );
            }
        }

        $record = DB::transaction(function () use ($instructorId, $year, $direction, $sourceHours, $targetHours, $rate, $data) {
            return HourExchangeRecord::create([
                'instructor_id' => $instructorId,
                'year' => $year,
                'period_mode' => $this->periodService->mode(),
                'direction' => $direction,
                'source_hours' => $sourceHours,
                'target_hours' => $targetHours,
                'rate' => $rate,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        });

        app(CalculationService::class)->rebuildDeclaration($yearlyResult);

        return $record;
    }

    public function recentExchanges(int $instructorId, string $year, int $limit = 10)
    {
        return HourExchangeRecord::query()
            ->currentPeriod()
            ->with('creator:id,name')
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function netResearchAdjustment(int $instructorId, string $year): float
    {
        $toCm = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_NCKH_TO_CM, 'source_hours');
        $fromCm = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_CM_TO_NCKH, 'target_hours');

        return round($fromCm - $toCm, 2);
    }

    public function netConversionAdjustment(int $instructorId, string $year): float
    {
        $fromNckh = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_NCKH_TO_CM, 'target_hours');
        $toNckh = $this->sumExchange($instructorId, $year, HourExchangeRecord::DIRECTION_CM_TO_NCKH, 'source_hours');

        return round($fromNckh - $toNckh, 2);
    }

    private function baseResearchHours(int $instructorId, string $year): float
    {
        $fromMembers = (float) ResearchRecordMember::query()
            ->where('instructor_id', $instructorId)
            ->whereHas('researchRecord', function ($query) use ($year) {
                $query->currentPeriod()
                    ->where('year', $year)
                    ->where('status', ResearchRecord::STATUS_APPROVED);
            })
            ->sum('converted_hours');

        if ($fromMembers > 0) {
            return round($fromMembers, 2);
        }

        return round((float) ResearchRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members')
            ->sum('converted_hours'), 2);
    }

    private function baseConversionHours(int $instructorId, string $year): float
    {
        return round((float) ConversionRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->where('is_external_invitation', false)
            ->where('status', ConversionRecord::STATUS_APPROVED)
            ->sum('converted_hours'), 2);
    }

    private function sumExchange(int $instructorId, string $year, string $direction, string $column): float
    {
        return round((float) HourExchangeRecord::query()
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->where('direction', $direction)
            ->sum($column), 2);
    }

    public function rateForDirection(string $direction): float
    {
        return $direction === HourExchangeRecord::DIRECTION_CM_TO_NCKH
            ? self::CM_TO_NCKH_RATE
            : self::NCKH_TO_CM_RATE;
    }
}
