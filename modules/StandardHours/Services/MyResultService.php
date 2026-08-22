<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\YearlyResult;

class MyResultService
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly PeriodService $periodService,
    ) {}

    public function paginate(?int $instructorId, array $filters = []): LengthAwarePaginator
    {
        if ($instructorId !== null) {
            $filters['instructor_id'] = $instructorId;
        }

        return $this->reportService->paginate($filters);
    }

    public function getForExport(?int $instructorId, array $filters = []): Collection
    {
        if ($instructorId !== null) {
            $filters['instructor_id'] = $instructorId;
        }

        return $this->reportService->getForExport($filters);
    }

    /**
     * @return array<string, string>
     */
    public function getYears(?int $instructorId = null): array
    {
        $resultQuery = YearlyResult::query()->currentPeriod();
        $conversionQuery = ConversionRecord::query()->currentPeriod();
        $researchQuery = ResearchRecord::query()->currentPeriod();

        if ($instructorId !== null) {
            $resultQuery->where('instructor_id', $instructorId);
            $conversionQuery->where('instructor_id', $instructorId);
            $researchQuery->byInstructor($instructorId);
        } elseif (ManagerUnitScope::isScoped()) {
            $unitIds = ManagerUnitScope::managedUnitIds();
            $scopeByUnit = fn (Builder $query) => $query->whereIn('unit_id', $unitIds);
            $resultQuery->whereHas('instructor', $scopeByUnit);
            $conversionQuery->whereHas('instructor', $scopeByUnit);
            $researchQuery->whereHas('instructor', $scopeByUnit);
        }

        $resultYears = $resultQuery->pluck('year');
        $conversionYears = $conversionQuery->pluck('year');
        $researchYears = $researchQuery->pluck('year');

        return collect([$this->periodService->currentYear()])
            ->merge($resultYears)
            ->merge($conversionYears)
            ->merge($researchYears)
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn ($year) => [$year => $this->periodService->label($year)])
            ->all();
    }

    public function findForYear(int $instructorId, ?string $year): ?YearlyResult
    {
        if (blank($year)) {
            return null;
        }

        return YearlyResult::with(['instructor.unit', 'objectType', 'position'])
            ->currentPeriod()
            ->where('instructor_id', $instructorId)
            ->where('year', $year)
            ->first();
    }
}
