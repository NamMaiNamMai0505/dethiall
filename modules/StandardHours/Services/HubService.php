<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Support\InstructorScope;

class HubService
{
    private const CHART_COLORS = [
        '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444',
        '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1',
        '#14b8a6', '#a855f7', '#eab308', '#64748b', '#0ea5e9',
    ];

    public function __construct(
        private readonly HourNormService $hourNormService,
    ) {}

    public function getSummaryStats(): array
    {
        $instructorId = InstructorScope::instructorId();
        $visibleInstructorIds = $this->visibleInstructorIds();

        $conversionQuery = ConversionRecord::query()->currentPeriod();
        $researchQuery = ResearchRecord::query()->currentPeriod();
        $externalQuery = ExternalActivityRecord::query()->currentPeriod();
        $yearlyQuery = YearlyResult::query()->currentPeriod();

        if ($visibleInstructorIds !== null) {
            $conversionQuery->whereIn('instructor_id', $visibleInstructorIds);
            $researchQuery->where(function ($q) use ($visibleInstructorIds) {
                $q->whereIn('instructor_id', $visibleInstructorIds)
                    ->orWhereHas('members', fn ($mq) => $mq->whereIn('instructor_id', $visibleInstructorIds));
            });
            $externalQuery->whereIn('instructor_id', $visibleInstructorIds);
            $yearlyQuery->whereIn('instructor_id', $visibleInstructorIds);
        }

        $memberHoursQuery = ResearchRecordMember::query()
            ->whereHas('researchRecord', fn ($q) => $q
                ->currentPeriod()
                ->where('status', ResearchRecord::STATUS_APPROVED));
        $legacyResearchQuery = ResearchRecord::query()
            ->currentPeriod()
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members');
        if ($visibleInstructorIds !== null) {
            $memberHoursQuery->whereIn('instructor_id', $visibleInstructorIds);
            $legacyResearchQuery->whereIn('instructor_id', $visibleInstructorIds);
        }
        $researchHours = (float) $memberHoursQuery->sum('converted_hours')
            + (float) $legacyResearchQuery->sum('converted_hours');

        return [
            'conversion' => [
                'total' => (clone $conversionQuery)->count(),
                'approved' => (clone $conversionQuery)->where('status', ConversionRecord::STATUS_APPROVED)->count(),
                'submitted' => (clone $conversionQuery)->where('status', ConversionRecord::STATUS_SUBMITTED)->count(),
                'draft' => (clone $conversionQuery)->where('status', ConversionRecord::STATUS_DRAFT)->count(),
                'total_hours' => round((float) (clone $conversionQuery)->where('status', ConversionRecord::STATUS_APPROVED)->sum('converted_hours'), 2),
            ],
            'research' => [
                'total' => (clone $researchQuery)->count(),
                'approved' => (clone $researchQuery)->where('status', ResearchRecord::STATUS_APPROVED)->count(),
                'submitted' => (clone $researchQuery)->where('status', ResearchRecord::STATUS_SUBMITTED)->count(),
                'draft' => (clone $researchQuery)->where('status', ResearchRecord::STATUS_DRAFT)->count(),
                'total_hours' => round($researchHours, 2),
            ],
            'external' => [
                'total' => (clone $externalQuery)->count(),
                'approved' => (clone $externalQuery)->where('status', ExternalActivityRecord::STATUS_APPROVED)->count(),
                'submitted' => (clone $externalQuery)->where('status', ExternalActivityRecord::STATUS_SUBMITTED)->count(),
                'draft' => (clone $externalQuery)->where('status', ExternalActivityRecord::STATUS_DRAFT)->count(),
                'total_hours' => 0,
            ],
            'calculated' => [
                'total' => (clone $yearlyQuery)->count(),
                'passed' => (clone $yearlyQuery)->where('meets_overall', true)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getChartData(): array
    {
        $instructorId = InstructorScope::instructorId();
        $visibleInstructorIds = $this->visibleInstructorIds();
        $latestYear = $this->resolveLatestYear($visibleInstructorIds);

        return [
            'year' => $latestYear,
            'top_instructors' => $this->getTopInstructors($instructorId, $latestYear, $visibleInstructorIds),
            'conversion_by_category' => $this->getConversionByCategory($visibleInstructorIds),
            'research_by_category' => $this->getResearchByCategory($visibleInstructorIds),
        ];
    }

    private function resolveLatestYear(?array $visibleInstructorIds): ?string
    {
        $query = YearlyResult::query()->currentPeriod();
        if ($visibleInstructorIds !== null) {
            $query->whereIn('instructor_id', $visibleInstructorIds);
        }
        $fromResults = $query->orderByDesc('year')->value('year');

        if ($fromResults) {
            return $fromResults;
        }

        $years = $this->hourNormService->getYears();

        return $years ? array_key_first($years) : null;
    }

    /**
     * @return array{labels: array<int, string>, hours: array<int, float>, source: string}
     */
    private function getTopInstructors(
        ?int $scopedInstructorId,
        ?string $year,
        ?array $visibleInstructorIds
    ): array {
        if ($scopedInstructorId !== null) {
            return ['labels' => [], 'hours' => [], 'source' => 'scoped'];
        }

        if ($year) {
            $yearlyQuery = YearlyResult::query()
                ->currentPeriod()
                ->with('instructor:id,name,code')
                ->where('year', $year);
            if ($visibleInstructorIds !== null) {
                $yearlyQuery->whereIn('instructor_id', $visibleInstructorIds);
            }
            $fromYearly = $yearlyQuery
                ->orderByDesc('total_standard_hours')
                ->limit(10)
                ->get();

            if ($fromYearly->isNotEmpty()) {
                return [
                    'labels' => $fromYearly->map(fn ($r) => $r->instructor->name ?? '—')->values()->all(),
                    'hours' => $fromYearly->map(fn ($r) => round((float) $r->total_standard_hours, 2))->values()->all(),
                    'source' => 'yearly',
                ];
            }
        }

        $conversionQuery = ConversionRecord::query()
            ->currentPeriod()
            ->select('instructor_id', DB::raw('SUM(converted_hours) as total_hours'))
            ->where('status', ConversionRecord::STATUS_APPROVED);
        if ($visibleInstructorIds !== null) {
            $conversionQuery->whereIn('instructor_id', $visibleInstructorIds);
        }
        $conversionHours = $conversionQuery
            ->groupBy('instructor_id')
            ->pluck('total_hours', 'instructor_id');

        $memberQuery = ResearchRecordMember::query()
            ->select('instructor_id', DB::raw('SUM(converted_hours) as total_hours'))
            ->whereHas('researchRecord', fn ($q) => $q
                ->currentPeriod()
                ->where('status', ResearchRecord::STATUS_APPROVED));
        if ($visibleInstructorIds !== null) {
            $memberQuery->whereIn('instructor_id', $visibleInstructorIds);
        }
        $memberHours = $memberQuery
            ->groupBy('instructor_id')
            ->pluck('total_hours', 'instructor_id');

        $legacyQuery = ResearchRecord::query()
            ->currentPeriod()
            ->select('instructor_id', DB::raw('SUM(converted_hours) as total_hours'))
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members');
        if ($visibleInstructorIds !== null) {
            $legacyQuery->whereIn('instructor_id', $visibleInstructorIds);
        }
        $legacyResearch = $legacyQuery
            ->groupBy('instructor_id')
            ->pluck('total_hours', 'instructor_id');

        $instructorIds = $conversionHours->keys()
            ->merge($memberHours->keys())
            ->merge($legacyResearch->keys())
            ->unique();

        $totals = $instructorIds->map(function ($id) use ($conversionHours, $memberHours, $legacyResearch) {
            return [
                'instructor_id' => (int) $id,
                'total' => (float) ($conversionHours[$id] ?? 0)
                    + (float) ($memberHours[$id] ?? 0)
                    + (float) ($legacyResearch[$id] ?? 0),
            ];
        })->sortByDesc('total')->take(10)->values();

        if ($totals->isEmpty()) {
            return ['labels' => [], 'hours' => [], 'source' => 'records'];
        }

        $instructors = Instructor::query()
            ->whereIn('id', $totals->pluck('instructor_id'))
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        return [
            'labels' => $totals->map(fn ($row) => $instructors[$row['instructor_id']]->name ?? '—')->all(),
            'hours' => $totals->map(fn ($row) => round($row['total'], 2))->all(),
            'source' => 'records',
        ];
    }

    /**
     * @return array{labels: array<int, string>, hours: array<int, float>, colors: array<int, string>}
     */
    private function getConversionByCategory(?array $visibleInstructorIds): array
    {
        $query = ConversionRecord::query()
            ->currentPeriod()
            ->select('conversion_category_id', DB::raw('SUM(converted_hours) as total_hours'))
            ->where('status', ConversionRecord::STATUS_APPROVED)
            ->groupBy('conversion_category_id');

        if ($visibleInstructorIds !== null) {
            $query->whereIn('instructor_id', $visibleInstructorIds);
        }

        $rows = $query->get();
        $categoryIds = $rows->pluck('conversion_category_id')->filter();
        $categories = ConversionCategory::query()
            ->whereIn('id', $categoryIds)
            ->pluck('name', 'id');

        $sorted = $rows->sortByDesc('total_hours')->values();

        return $this->formatPieDataset(
            $sorted->map(fn ($row) => $categories[$row->conversion_category_id] ?? 'Khác')->all(),
            $sorted->map(fn ($row) => round((float) $row->total_hours, 2))->all(),
        );
    }

    /**
     * @return array{labels: array<int, string>, hours: array<int, float>, colors: array<int, string>}
     */
    private function getResearchByCategory(?array $visibleInstructorIds): array
    {
        $fromMembers = ResearchRecordMember::query()
            ->join('instructor_research_records', 'research_record_members.research_record_id', '=', 'instructor_research_records.id')
            ->select('instructor_research_records.research_category_id', DB::raw('SUM(research_record_members.converted_hours) as total_hours'))
            ->where('instructor_research_records.status', ResearchRecord::STATUS_APPROVED)
            ->where('instructor_research_records.period_mode', app(PeriodService::class)->mode())
            ->when($visibleInstructorIds !== null, fn ($q) => $q->whereIn('research_record_members.instructor_id', $visibleInstructorIds))
            ->groupBy('instructor_research_records.research_category_id')
            ->get();

        $fromLegacy = ResearchRecord::query()
            ->currentPeriod()
            ->select('research_category_id', DB::raw('SUM(converted_hours) as total_hours'))
            ->where('status', ResearchRecord::STATUS_APPROVED)
            ->whereDoesntHave('members')
            ->when($visibleInstructorIds !== null, fn ($q) => $q->whereIn('instructor_id', $visibleInstructorIds))
            ->groupBy('research_category_id')
            ->get();

        $totals = [];
        foreach ($fromMembers->concat($fromLegacy) as $row) {
            $categoryId = $row->research_category_id;
            $totals[$categoryId] = ($totals[$categoryId] ?? 0) + (float) $row->total_hours;
        }

        arsort($totals);

        $categories = ResearchCategory::query()
            ->whereIn('id', array_keys($totals))
            ->pluck('name', 'id');

        return $this->formatPieDataset(
            array_map(fn ($id) => $categories[$id] ?? 'Khác', array_keys($totals)),
            array_map(fn ($hours) => round($hours, 2), array_values($totals)),
        );
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, float>  $hours
     * @return array{labels: array<int, string>, hours: array<int, float>, colors: array<int, string>}
     */
    private function formatPieDataset(array $labels, array $hours): array
    {
        return [
            'labels' => $labels,
            'hours' => $hours,
            'colors' => collect($labels)->keys()->map(fn ($i) => self::CHART_COLORS[$i % count(self::CHART_COLORS)])->all(),
        ];
    }

    /**
     * null = super-admin/toàn hệ thống; mảng = đúng phạm vi người đang đăng nhập.
     *
     * @return list<int>|null
     */
    private function visibleInstructorIds(): ?array
    {
        if ($instructorId = InstructorScope::instructorId()) {
            return [$instructorId];
        }

        if (! ManagerUnitScope::isScoped()) {
            return null;
        }

        return Instructor::query()
            ->whereIn('unit_id', ManagerUnitScope::managedUnitIds())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all() ?: [-1];
    }
}
