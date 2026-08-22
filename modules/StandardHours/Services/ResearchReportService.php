<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Support\ReportDocumentLayout;

class ResearchReportService
{
    public function getFilterOptions(): array
    {
        return app(CalculationService::class)->getFilterOptions();
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = ResearchRecordMember::query()
            ->with([
                'instructor.unit',
                'researchRecord.researchCategory',
            ])
            ->whereHas('researchRecord', function (Builder $query) use ($filters) {
                $query->currentPeriod()
                    ->where('status', ResearchRecord::STATUS_APPROVED);

                if (! empty($filters['from_date'])) {
                    $query->whereDate('acceptance_date', '>=', $filters['from_date']);
                }

                if (! empty($filters['to_date'])) {
                    $query->whereDate('acceptance_date', '<=', $filters['to_date']);
                }
            });

        if (! empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        ManagerUnitScope::applyToFilters($filters);

        // Lọc theo khoa của thành viên (GV trên dòng), không phải người kê khai
        $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
        if ($unitIds !== []) {
            $query->whereHas('instructor', fn (Builder $q) => $q->whereIn('unit_id', $unitIds));
        } elseif (! empty($filters['unit_id'])) {
            $query->whereHas('instructor', fn (Builder $q) => $q->where('unit_id', $filters['unit_id']));
        }

        $members = $query
            ->join('instructor_research_records', 'research_record_members.research_record_id', '=', 'instructor_research_records.id')
            ->orderBy('instructor_research_records.acceptance_date')
            ->orderBy('instructor_research_records.id')
            ->orderBy('research_record_members.sort_order')
            ->select('research_record_members.*')
            ->get();

        if ($members->isEmpty()) {
            $members = $this->legacyExportRows($filters);
        }

        return $members
            ->sortBy([
                fn (ResearchRecordMember $member) => mb_strtolower($member->instructor->unit->name ?? 'zzz'),
                fn (ResearchRecordMember $member) => $member->researchRecord?->acceptance_date?->format('Y-m-d') ?? '',
                fn (ResearchRecordMember $member) => $member->research_record_id,
                fn (ResearchRecordMember $member) => (int) $member->sort_order,
            ])
            ->values();
    }

    /**
     * Aggregate by member's unit (khoa).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summarizeByUnit(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (ResearchRecordMember $member) => $member->instructor->unit_id ?? 0)
            ->map(function (Collection $group, $unitId) {
                $unit = $group->first()?->instructor?->unit;
                $uniqueProducts = $group->unique(fn (ResearchRecordMember $m) => $m->research_record_id);

                return [
                    'unit_id' => (int) $unitId,
                    'unit_name' => $unit->name ?? 'Chưa phân khoa',
                    'unit_code' => $unit->code ?? '',
                    'product_count' => $uniqueProducts->count(),
                    'member_count' => $group->count(),
                    'instructor_count' => $group->pluck('instructor_id')->unique()->count(),
                    // Thời gian thực hiện: cộng duration_years theo từng sản phẩm (không nhân theo số thành viên)
                    'total_duration_years' => (float) $uniqueProducts->sum(
                        fn (ResearchRecordMember $m) => (float) ($m->researchRecord->duration_years ?? 0)
                    ),
                    'total_converted_hours' => (float) $group->sum(
                        fn (ResearchRecordMember $m) => (float) ($m->converted_hours
                            ?? $m->researchRecord->converted_hours
                            ?? 0)
                    ),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['unit_name']))
            ->values();
    }

    /**
     * School totals from full member rows (unique products school-wide, avoid double-count).
     *
     * @param  Collection<int, array<string, mixed>>  $unitSummaries
     * @param  Collection<int, ResearchRecordMember>  $rows
     * @return array<string, mixed>
     */
    public function summarizeSchool(Collection $unitSummaries, Collection $rows): array
    {
        $uniqueProducts = $rows->unique(
            fn (ResearchRecordMember $member) => $member->research_record_id
        );

        return [
            'unit_count' => $unitSummaries->count(),
            'product_count' => $uniqueProducts->count(),
            'member_count' => $rows->count(),
            'instructor_count' => $rows->pluck('instructor_id')->unique()->count(),
            'total_duration_years' => (float) $uniqueProducts->sum(
                fn (ResearchRecordMember $member) => (float) ($member->researchRecord->duration_years ?? 0)
            ),
            'total_converted_hours' => (float) $rows->sum(
                fn (ResearchRecordMember $member) => (float) ($member->converted_hours
                    ?? $member->researchRecord->converted_hours
                    ?? 0)
            ),
            'unit_name' => 'TOÀN TRƯỜNG',
            'unit_code' => '',
        ];
    }

    private function legacyExportRows(array $filters): Collection
    {
        $query = ResearchRecord::with(['instructor.unit', 'researchCategory'])
            ->currentPeriod()
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members');

        if (! empty($filters['from_date'])) {
            $query->whereDate('acceptance_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('acceptance_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
        if ($unitIds !== []) {
            $query->whereHas('instructor', fn ($q) => $q->whereIn('unit_id', $unitIds));
        } elseif (! empty($filters['unit_id'])) {
            $query->whereHas('instructor', fn ($q) => $q->where('unit_id', $filters['unit_id']));
        }

        return $query->orderBy('acceptance_date')->get()->map(function (ResearchRecord $record) {
            $member = new ResearchRecordMember([
                'research_record_id' => $record->id,
                'instructor_id' => $record->instructor_id,
                'role' => $record->role,
                'participation_type' => $record->participation_type,
                'contribution_percent' => $record->member_count > 3 ? 100 : null,
                'converted_hours' => $record->converted_hours,
                'is_declarant' => true,
                'sort_order' => 0,
            ]);
            $member->setRelation('instructor', $record->instructor);
            $member->setRelation('researchRecord', $record);

            return $member;
        });
    }
}
