<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Support\InstructorScope;

class ExternalActivityService
{
    public function __construct(
        private readonly PeriodService $periodService,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        InstructorScope::apply($filters);

        $query = ExternalActivityRecord::query()
            ->with(['instructor.unit', 'creator', 'approver'])
            ->currentPeriod()
            ->search($filters['search'] ?? null)
            ->when(
                filled($filters['instructor_id'] ?? null),
                fn ($builder) => $builder->where('instructor_id', $filters['instructor_id'])
            )
            ->when(
                filled($filters['activity_type'] ?? null),
                fn ($builder) => $builder->where('activity_type', $filters['activity_type'])
            )
            ->when(
                filled($filters['year'] ?? null),
                fn ($builder) => $builder->where('year', $filters['year'])
            )
            ->when(
                filled($filters['status'] ?? null),
                fn ($builder) => $builder->where('status', $filters['status'])
            )
            ->when(
                filled($filters['from_date'] ?? null),
                fn ($builder) => $builder->whereDate('from_date', '>=', $filters['from_date'])
            )
            ->when(
                filled($filters['to_date'] ?? null),
                fn ($builder) => $builder->whereDate('from_date', '<=', $filters['to_date'])
            );

        $unitIds = $filters['unit_ids'] ?? null;
        if (is_array($unitIds) && $unitIds !== []) {
            $query->whereHas('instructor', fn ($builder) => $builder->whereIn('unit_id', $unitIds));
        }

        return $query
            ->latest('from_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data, ?UploadedFile $evidence = null): ExternalActivityRecord
    {
        return DB::transaction(function () use ($data, $evidence) {
            $mode = $this->periodService->mode();
            $data['year'] = $this->periodService->resolveYearForDate($data['from_date'], $mode);
            $data['period_mode'] = $mode;
            $data['status'] = ExternalActivityRecord::STATUS_DRAFT;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            if ($evidence) {
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            return ExternalActivityRecord::query()->create($data);
        });
    }

    public function update(
        ExternalActivityRecord $record,
        array $data,
        ?UploadedFile $evidence = null
    ): ExternalActivityRecord {
        $this->ensureEditable($record);

        return DB::transaction(function () use ($record, $data, $evidence) {
            $mode = $record->period_mode ?: $this->periodService->mode();
            $data['year'] = $this->periodService->resolveYearForDate($data['from_date'], $mode);
            $data['period_mode'] = $mode;
            $data['updated_by'] = Auth::id();

            if ($evidence) {
                $this->deleteEvidence($record->evidence_path);
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            $record->update($data);

            return $record->fresh(['instructor.unit', 'creator', 'updater', 'approver']);
        });
    }

    public function delete(ExternalActivityRecord $record): void
    {
        $this->ensureEditable($record);
        $record->delete();
    }

    public function submit(ExternalActivityRecord $record): ExternalActivityRecord
    {
        if (! $record->isEditable()) {
            throw new \RuntimeException('Chỉ có thể gửi bản nháp hoặc bản đã bị từ chối.');
        }

        $record->update([
            'status' => ExternalActivityRecord::STATUS_SUBMITTED,
            'review_note' => null,
            'approved_by' => null,
            'approved_at' => null,
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function approve(ExternalActivityRecord $record, ?string $note = null): ExternalActivityRecord
    {
        $this->ensureSubmitted($record);

        $record->update([
            'status' => ExternalActivityRecord::STATUS_APPROVED,
            'review_note' => $note,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function reject(ExternalActivityRecord $record, ?string $note = null): ExternalActivityRecord
    {
        $this->ensureSubmitted($record);

        $record->update([
            'status' => ExternalActivityRecord::STATUS_REJECTED,
            'review_note' => $note,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function formOptions(): array
    {
        return [
            'instructors' => InstructorScope::instructorsQuery()->get(['id', 'name', 'code', 'unit_id']),
            'isInstructorView' => InstructorScope::instructorId() !== null,
            'activityTypes' => ExternalActivityRecord::activityTypeOptions(),
            'years' => $this->periodService->options(),
            'periodModeLabel' => $this->periodService->modeLabel(),
        ];
    }

    public function filterOptions(): array
    {
        return array_merge($this->formOptions(), [
            'statuses' => ExternalActivityRecord::statusOptions(),
        ]);
    }

    private function ensureEditable(ExternalActivityRecord $record): void
    {
        if (! $record->canBeEditedBy(Auth::user())) {
            throw new \RuntimeException('Không thể sửa kê khai đang chờ duyệt hoặc đã duyệt.');
        }
    }

    private function ensureSubmitted(ExternalActivityRecord $record): void
    {
        if ($record->status !== ExternalActivityRecord::STATUS_SUBMITTED) {
            throw new \RuntimeException('Chỉ có thể xử lý kê khai đã gửi duyệt.');
        }
    }

    private function storeEvidence(UploadedFile $file): string
    {
        return $file->store('standard-hours/external-activity-evidence', 'public');
    }

    private function deleteEvidence(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
