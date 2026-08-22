<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Support\ReportDocumentLayout;

class ConversionReportService
{
    public function getFilterOptions(): array
    {
        return app(CalculationService::class)->getFilterOptions();
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = ConversionRecord::with(['instructor.unit', 'conversionCategory'])
            ->currentPeriod()
            ->where('status', ConversionRecord::STATUS_APPROVED);

        if (! empty($filters['from_date'])) {
            $query->whereDate('activity_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('activity_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        ManagerUnitScope::applyToFilters($filters);

        $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
        if ($unitIds !== []) {
            $query->whereHas('instructor', fn (Builder $q) => $q->whereIn('unit_id', $unitIds));
        } elseif (! empty($filters['unit_id'])) {
            $query->whereHas('instructor', fn (Builder $q) => $q->where('unit_id', $filters['unit_id']));
        }

        return $query
            ->orderBy('activity_date')
            ->orderBy('instructor_id')
            ->get()
            ->sortBy([
                fn (ConversionRecord $record) => mb_strtolower($record->instructor->unit->name ?? 'zzz'),
                fn (ConversionRecord $record) => mb_strtolower($record->instructor->name ?? ''),
                fn (ConversionRecord $record) => $record->activity_date?->format('Y-m-d') ?? '',
            ])
            ->values();
    }

    /**
     * Aggregate conversion records by instructor unit.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summarizeByUnit(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (ConversionRecord $record) => $record->instructor->unit_id ?? 0)
            ->map(function (Collection $group, $unitId) {
                $unit = $group->first()?->instructor?->unit;

                return [
                    'unit_id' => (int) $unitId,
                    'unit_name' => $unit->name ?? 'Chưa phân khoa',
                    'unit_code' => $unit->code ?? '',
                    'activity_count' => $group->count(),
                    'instructor_count' => $group->pluck('instructor_id')->unique()->count(),
                    'total_quantity' => (float) $group->sum('quantity'),
                    'total_converted_hours' => (float) $group->sum('converted_hours'),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['unit_name']))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $unitSummaries
     * @return array<string, mixed>
     */
    public function summarizeSchool(Collection $unitSummaries): array
    {
        return [
            'unit_count' => $unitSummaries->count(),
            'activity_count' => (int) $unitSummaries->sum('activity_count'),
            'instructor_count' => (int) $unitSummaries->sum('instructor_count'),
            'total_quantity' => (float) $unitSummaries->sum('total_quantity'),
            'total_converted_hours' => (float) $unitSummaries->sum('total_converted_hours'),
            'unit_name' => 'TOÀN TRƯỜNG',
            'unit_code' => '',
        ];
    }
}
