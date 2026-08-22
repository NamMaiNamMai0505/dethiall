<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Support\ReportDocumentLayout;
use Modules\StandardHours\Support\YearlyResultFormatter;

class ReportService
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->buildQuery($filters)
            ->orderByDesc('total_standard_hours')
            ->paginate($this->resolvePerPage($filters))
            ->withQueryString();
    }

    public function getForExport(array $filters = []): Collection
    {
        return $this->buildQuery($filters)
            ->get()
            ->sortBy([
                fn (YearlyResult $result) => mb_strtolower($result->instructor->unit->name ?? 'zzz'),
                fn (YearlyResult $result) => mb_strtolower($result->instructor->name ?? ''),
            ])
            ->values();
    }

    public function getSummary(array $filters = []): array
    {
        $results = $this->buildQuery($filters)->get();

        return [
            'total' => $results->count(),
            'passed' => $results->where('meets_overall', true)->count(),
            'failed' => $results->where('meets_overall', false)->count(),
            'avg_standard_hours' => round((float) $results->avg('total_standard_hours'), 2),
            'avg_research_hours' => round((float) $results->avg('research_hours'), 2),
        ];
    }

    /**
     * Aggregate yearly results by instructor unit (khoa).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summarizeByUnit(Collection $results): Collection
    {
        return $results
            ->groupBy(fn (YearlyResult $result) => $result->instructor->unit_id ?? 0)
            ->map(function (Collection $group, $unitId) {
                $unit = $group->first()?->instructor?->unit;

                return [
                    'unit_id' => (int) $unitId,
                    'unit_name' => $unit->name ?? 'Chưa phân khoa',
                    'unit_code' => $unit->code ?? '',
                    'instructor_count' => $group->count(),
                    'standard_norm_hours' => (float) $group->sum('standard_norm_hours'),
                    'total_standard_hours' => (float) $group->sum('total_standard_hours'),
                    'standard_difference' => (float) $group->sum('standard_difference'),
                    'min_classroom_hours' => (float) $group->sum('min_classroom_hours'),
                    'teaching_hours' => (float) $group->sum('teaching_hours'),
                    'classroom_difference' => (float) $group->sum(
                        fn (YearlyResult $result) => YearlyResultFormatter::classroomDifference($result)
                    ),
                    'conversion_hours' => (float) $group->sum('conversion_hours'),
                    'research_norm_hours' => (float) $group->sum('research_norm_hours'),
                    'research_hours' => (float) $group->sum('research_hours'),
                    'research_difference' => (float) $group->sum('research_difference'),
                    'passed' => $group->where('meets_overall', true)->count(),
                    'failed' => $group->where('meets_overall', false)->count(),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['unit_name']))
            ->values();
    }

    /**
     * School-level total from unit summaries.
     *
     * @param  Collection<int, array<string, mixed>>  $unitSummaries
     * @return array<string, mixed>
     */
    public function summarizeSchool(Collection $unitSummaries): array
    {
        return [
            'unit_count' => $unitSummaries->count(),
            'instructor_count' => (int) $unitSummaries->sum('instructor_count'),
            'standard_norm_hours' => (float) $unitSummaries->sum('standard_norm_hours'),
            'total_standard_hours' => (float) $unitSummaries->sum('total_standard_hours'),
            'standard_difference' => (float) $unitSummaries->sum('standard_difference'),
            'min_classroom_hours' => (float) $unitSummaries->sum('min_classroom_hours'),
            'teaching_hours' => (float) $unitSummaries->sum('teaching_hours'),
            'classroom_difference' => (float) $unitSummaries->sum('classroom_difference'),
            'conversion_hours' => (float) $unitSummaries->sum('conversion_hours'),
            'research_norm_hours' => (float) $unitSummaries->sum('research_norm_hours'),
            'research_hours' => (float) $unitSummaries->sum('research_hours'),
            'research_difference' => (float) $unitSummaries->sum('research_difference'),
            'passed' => (int) $unitSummaries->sum('passed'),
            'failed' => (int) $unitSummaries->sum('failed'),
        ];
    }

    public function getFilterOptions(): array
    {
        return $this->calculationService->getFilterOptions();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = YearlyResult::with([
            'instructor.unit',
            'objectType',
            'position',
        ])->currentPeriod();

        $query->byYear($filters['year'] ?? null);
        $query->byInstructor($filters['instructor_id'] ?? null);
        $query->byOverall($filters['overall_result'] ?? null);
        $query->search($filters['search'] ?? null);

        if (! empty($filters['from_date'])) {
            $query->where(function (Builder $dateQuery) use ($filters) {
                $dateQuery
                    ->whereDate('declaration_to_date', '>=', $filters['from_date'])
                    ->orWhere(function (Builder $fallbackQuery) use ($filters) {
                        $fallbackQuery
                            ->whereNull('declaration_to_date')
                            ->whereDate('calculated_at', '>=', $filters['from_date']);
                    });
            });
        }
        if (! empty($filters['to_date'])) {
            $query->where(function (Builder $dateQuery) use ($filters) {
                $dateQuery
                    ->whereDate('declaration_from_date', '<=', $filters['to_date'])
                    ->orWhere(function (Builder $fallbackQuery) use ($filters) {
                        $fallbackQuery
                            ->whereNull('declaration_from_date')
                            ->whereDate('calculated_at', '<=', $filters['to_date']);
                    });
            });
        }

        ManagerUnitScope::applyToFilters($filters);

        $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
        if ($unitIds !== []) {
            $query->whereHas('instructor', fn (Builder $q) => $q->whereIn('unit_id', $unitIds));
        } else {
            $query->byUnit($filters['unit_id'] ?? null);
        }

        return $query;
    }

    private function resolvePerPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            return 10;
        }

        return $perPage;
    }
}
