<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Models\ResearchRecordMember;
use Modules\StandardHours\Support\InstructorScope;

class ResearchService
{
    public function __construct(
        private readonly HourNormService $hourNormService,
        private readonly ResearchDistributionService $distributionService,
        private readonly PeriodService $periodService,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters);

        $sortBy = $filters['sort_by'] ?? 'acceptance_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{submitted:int,approved:int}
     */
    public function assessmentCounts(array $filters = []): array
    {
        unset($filters['status'], $filters['page']);
        $counts = $this->filteredQuery($filters)
            ->reorder()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            ResearchRecord::STATUS_SUBMITTED => (int) ($counts[ResearchRecord::STATUS_SUBMITTED] ?? 0),
            ResearchRecord::STATUS_APPROVED => (int) ($counts[ResearchRecord::STATUS_APPROVED] ?? 0),
        ];
    }

    private function filteredQuery(array &$filters)
    {
        InstructorScope::apply($filters);

        $query = ResearchRecord::with([
            'instructor.unit',
            'researchCategory',
            'creator',
            'members.instructor',
        ])->currentPeriod();

        $query->search($filters['search'] ?? null);
        $query->byInstructor($filters['instructor_id'] ?? null);
        $query->byCategory($filters['research_category_id'] ?? null);
        $query->byStatus($filters['status'] ?? null);
        $query->byYear($filters['year'] ?? null);

        $unitIds = $filters['unit_ids'] ?? null;
        if (is_array($unitIds) && $unitIds !== []) {
            $query->where(function ($q) use ($unitIds) {
                $q->whereHas('instructor', fn ($iq) => $iq->whereIn('unit_id', $unitIds))
                    ->orWhereHas('members.instructor', fn ($iq) => $iq->whereIn('unit_id', $unitIds));
            });
        } elseif (! empty($filters['unit_id'])) {
            $unitId = $filters['unit_id'];
            $query->where(function ($q) use ($unitId) {
                $q->whereHas('instructor', fn ($iq) => $iq->where('unit_id', $unitId))
                    ->orWhereHas('members.instructor', fn ($iq) => $iq->where('unit_id', $unitId));
            });
        }

        return $query;
    }

    public function create(array $data, ?UploadedFile $evidence = null): ResearchRecord
    {
        return DB::transaction(function () use ($data, $evidence) {
            $category = ResearchCategory::findOrFail($data['research_category_id']);
            $data = $this->preparePersonalDeclaration($data, $category);
            $data['year'] = $this->resolveYear($data['acceptance_date']);
            $data['period_mode'] = $this->periodService->mode();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ResearchRecord::STATUS_DRAFT;

            if ($evidence) {
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            $record = ResearchRecord::create($data);
            $this->syncDeclarantMember($record);

            return $record->fresh([
                'instructor.unit',
                'researchCategory',
                'creator',
                'members.instructor',
            ]);
        });
    }

    public function update(ResearchRecord $record, array $data, ?UploadedFile $evidence = null): ResearchRecord
    {
        $this->ensureEditable($record);

        return DB::transaction(function () use ($record, $data, $evidence) {
            $category = ResearchCategory::findOrFail($data['research_category_id']);
            $data = $this->preparePersonalDeclaration($data, $category);
            $data['year'] = $this->resolveYear($data['acceptance_date']);
            $data['period_mode'] = $record->period_mode ?: $this->periodService->mode();
            $data['updated_by'] = Auth::id();

            if ($evidence) {
                $this->deleteEvidence($record->evidence_path);
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            $record->update($data);
            $record->members()->delete();
            $this->syncDeclarantMember($record);

            return $record->fresh([
                'instructor.unit',
                'researchCategory',
                'creator',
                'updater',
                'members.instructor',
            ]);
        });
    }

    public function delete(ResearchRecord $record): void
    {
        $this->ensureEditable($record);

        DB::transaction(function () use ($record) {
            $this->deleteEvidence($record->evidence_path);
            $record->members()->delete();
            $record->delete();
        });
    }

    public function submit(ResearchRecord $record): ResearchRecord
    {
        if (! $record->isEditable()) {
            throw new \RuntimeException('Không thể gửi kê khai ở trạng thái hiện tại.');
        }

        $record->update([
            'status' => ResearchRecord::STATUS_SUBMITTED,
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function approve(ResearchRecord $record): ResearchRecord
    {
        if ($record->status !== ResearchRecord::STATUS_SUBMITTED) {
            throw new \RuntimeException('Chỉ có thể thẩm định kê khai đang chờ thẩm định.');
        }

        $record->update([
            'status' => ResearchRecord::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function reject(ResearchRecord $record): ResearchRecord
    {
        if ($record->status !== ResearchRecord::STATUS_SUBMITTED) {
            throw new \RuntimeException('Chỉ có thể từ chối kê khai đã gửi.');
        }

        $record->update([
            'status' => ResearchRecord::STATUS_REJECTED,
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function getFilterOptions(): array
    {
        $instructors = InstructorScope::instructorsQuery()
            ->with('objectType')
            ->get(['id', 'name', 'code', 'unit_id', 'object_type_id']);
        $periodYear = $this->periodService->currentYear();
        $periodMode = $this->periodService->mode();
        $researchNormsByInstructor = $instructors->mapWithKeys(function ($instructor) use ($periodYear, $periodMode) {
            $objectType = $instructor->objectType;

            return [(string) $instructor->id => [
                'instructor_id' => (int) $instructor->id,
                'instructor_code' => (string) $instructor->code,
                'instructor_name' => (string) $instructor->name,
                'object_type_code' => (string) ($objectType?->code ?? ''),
                'object_type_name' => (string) ($objectType?->name ?? ''),
                'research_norm_hours' => $objectType ? (float) $objectType->research_hours : null,
                'period_mode_label' => $this->periodService->modeLabel($periodMode),
                'period_label' => $this->periodService->label($periodYear, $periodMode),
            ]];
        })->all();
        $scopedInstructorId = InstructorScope::instructorId();

        return [
            'instructors' => $instructors,
            'isInstructorView' => $scopedInstructorId !== null,
            'categories' => ResearchCategory::active()->orderBy('name')->get(['id', 'name', 'code', 'research_hours']),
            'years' => $this->hourNormService->getYears(),
            'statuses' => ResearchRecord::getStatusOptions(),
            'participationTypes' => ResearchRecord::getParticipationTypeOptions(),
            'distributionRules' => $this->distributionService->getRules(),
            'researchNormsByInstructor' => $researchNormsByInstructor,
            'currentResearchNorm' => $scopedInstructorId
                ? ($researchNormsByInstructor[(string) $scopedInstructorId] ?? null)
                : null,
        ];
    }

    public function calculateTotalBaseHours(ResearchCategory $category, int $durationYears = 1): float
    {
        $baseHours = (float) $category->research_hours;
        $durationYears = max(1, $durationYears);

        if ($durationYears > 1) {
            $baseHours = $baseHours / $durationYears;
        }

        return round($baseHours, 2);
    }

    public function resolveYear(string $date): string
    {
        return (string) $this->periodService->resolveYearForDate($date);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePersonalDeclaration(array $data, ResearchCategory $category): array
    {
        $durationYears = max(1, (int) ($data['duration_years'] ?? 1));
        $memberCount = max(1, (int) ($data['member_count'] ?? 1));
        $participationType = $data['participation_type'] ?? ResearchRecord::PARTICIPATION_LEAD;
        $contributionPercent = $participationType === ResearchRecord::PARTICIPATION_MEMBER
            && isset($data['contribution_percent'])
            && $data['contribution_percent'] !== ''
                ? (float) $data['contribution_percent']
                : null;

        $annualProductHours = $this->calculateTotalBaseHours($category, $durationYears);
        $calculatedHours = $this->distributionService->calculatePersonalHours(
            $annualProductHours,
            $memberCount,
            $participationType,
            $contributionPercent,
        );
        $declaredHours = $participationType === ResearchRecord::PARTICIPATION_MEMBER
            && isset($data['converted_hours'])
            && $data['converted_hours'] !== ''
            ? round((float) $data['converted_hours'], 2)
            : $calculatedHours;

        $data['member_count'] = $memberCount;
        $data['duration_years'] = $durationYears;
        $data['participation_type'] = $participationType;
        $data['role'] = null;
        $data['contribution_percent'] = $contributionPercent;
        $data['annual_product_hours'] = $annualProductHours;
        $data['calculated_hours'] = $calculatedHours;
        $data['converted_hours'] = $declaredHours;
        $data['hours_adjustment_note'] = $participationType === ResearchRecord::PARTICIPATION_MEMBER
            ? ($data['hours_adjustment_note'] ?? null)
            : null;

        return $data;
    }

    private function syncDeclarantMember(ResearchRecord $record): void
    {
        ResearchRecordMember::create([
            'research_record_id' => $record->id,
            'instructor_id' => $record->instructor_id,
            'role' => null,
            'participation_type' => $record->participation_type,
            'contribution_percent' => $record->contribution_percent,
            'converted_hours' => $record->converted_hours,
            'is_declarant' => true,
            'sort_order' => 0,
        ]);
    }

    private function ensureEditable(ResearchRecord $record): void
    {
        if (! $record->canBeEditedBy(Auth::user())) {
            throw new \RuntimeException('Không thể sửa kê khai đã thẩm định hoặc đang chờ thẩm định.');
        }
    }

    private function storeEvidence(UploadedFile $file): string
    {
        return $file->store('standard-hours/research-evidence', 'public');
    }

    private function deleteEvidence(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
