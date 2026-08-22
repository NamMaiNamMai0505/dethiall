<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Support\InstructorScope;

class ConversionService
{
    public function __construct(
        private readonly HourNormService $hourNormService,
        private readonly PeriodService $periodService,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters);

        $sortBy = $filters['sort_by'] ?? 'activity_date';
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
            ConversionRecord::STATUS_SUBMITTED => (int) ($counts[ConversionRecord::STATUS_SUBMITTED] ?? 0),
            ConversionRecord::STATUS_APPROVED => (int) ($counts[ConversionRecord::STATUS_APPROVED] ?? 0),
        ];
    }

    private function filteredQuery(array &$filters)
    {
        InstructorScope::apply($filters);

        $query = ConversionRecord::with([
            'instructor.unit',
            'conversionCategory',
            'creator',
        ])->currentPeriod();

        $query->search($filters['search'] ?? null);
        $query->byInstructor($filters['instructor_id'] ?? null);
        $query->byCategory($filters['conversion_category_id'] ?? null);
        $query->byStatus($filters['status'] ?? null);
        $query->byYear($filters['year'] ?? null);

        $unitIds = $filters['unit_ids'] ?? null;
        if (is_array($unitIds) && $unitIds !== []) {
            $query->whereHas('instructor', fn ($q) => $q->whereIn('unit_id', $unitIds));
        } elseif (! empty($filters['unit_id'])) {
            $query->whereHas('instructor', fn ($q) => $q->where('unit_id', $filters['unit_id']));
        }

        return $query;
    }

    public function create(array $data, ?UploadedFile $evidence = null): ConversionRecord
    {
        return DB::transaction(function () use ($data, $evidence) {
            $category = ConversionCategory::findOrFail($data['conversion_category_id']);
            $this->ensureManualCategory($category);
            $quantity = (float) $data['quantity'];

            $data['converted_hours'] = $category->calculateHours($quantity);
            $data['year'] = $this->resolveYear($data['activity_date']);
            $data['period_mode'] = $this->periodService->mode();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = $data['status'] ?? ConversionRecord::STATUS_DRAFT;

            if ($evidence) {
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            return ConversionRecord::create($data);
        });
    }

    public function update(ConversionRecord $record, array $data, ?UploadedFile $evidence = null): ConversionRecord
    {
        $this->ensureEditable($record);

        return DB::transaction(function () use ($record, $data, $evidence) {
            $category = ConversionCategory::findOrFail($data['conversion_category_id']);
            $this->ensureManualCategory($category);
            $quantity = (float) $data['quantity'];

            $data['converted_hours'] = $category->calculateHours($quantity);
            $data['year'] = $this->resolveYear($data['activity_date']);
            $data['period_mode'] = $record->period_mode ?: $this->periodService->mode();
            $data['updated_by'] = Auth::id();

            if ($evidence) {
                $this->deleteEvidence($record->evidence_path);
                $data['evidence_path'] = $this->storeEvidence($evidence);
            }

            $record->update($data);

            return $record->fresh([
                'instructor.unit',
                'conversionCategory',
                'creator',
                'updater',
            ]);
        });
    }

    public function delete(ConversionRecord $record): void
    {
        $this->ensureEditable($record);

        DB::transaction(function () use ($record) {
            $this->deleteEvidence($record->evidence_path);
            $record->delete();
        });
    }

    public function submit(ConversionRecord $record): ConversionRecord
    {
        if (! $record->isEditable()) {
            throw new \RuntimeException('Không thể gửi kê khai ở trạng thái hiện tại.');
        }

        $record->update([
            'status' => ConversionRecord::STATUS_SUBMITTED,
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function approve(ConversionRecord $record): ConversionRecord
    {
        if ($record->status !== ConversionRecord::STATUS_SUBMITTED) {
            throw new \RuntimeException('Chỉ có thể thẩm định kê khai đang chờ thẩm định.');
        }

        $record->update([
            'status' => ConversionRecord::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function reject(ConversionRecord $record): ConversionRecord
    {
        if ($record->status !== ConversionRecord::STATUS_SUBMITTED) {
            throw new \RuntimeException('Chỉ có thể từ chối kê khai đã gửi.');
        }

        $record->update([
            'status' => ConversionRecord::STATUS_REJECTED,
            'updated_by' => Auth::id(),
        ]);

        return $record->fresh();
    }

    public function getFilterOptions(): array
    {
        return [
            'instructors' => InstructorScope::instructorsQuery()->get(['id', 'name', 'code', 'unit_id']),
            'isInstructorView' => InstructorScope::instructorId() !== null,
            'categories' => ConversionCategory::active()->manual()->orderBy('name')->get(['id', 'name', 'code', 'unit', 'conversion_method', 'coefficient', 'fixed_hours']),
            'years' => $this->hourNormService->getYears(),
            'statuses' => ConversionRecord::getStatusOptions(),
        ];
    }

    public function resolveYear(string $date): string
    {
        return (string) $this->periodService->resolveYearForDate($date);
    }

    private function ensureManualCategory(ConversionCategory $category): void
    {
        if ($category->isScheduleGenerated()) {
            throw new \RuntimeException(
                'Giờ trực tiếp giảng dạy được hệ thống tự lấy từ lịch phân công, giảng viên không cần kê khai.'
            );
        }
    }

    private function ensureEditable(ConversionRecord $record): void
    {
        if (! $record->canBeEditedBy(Auth::user())) {
            throw new \RuntimeException('Không thể sửa kê khai đã thẩm định hoặc đang chờ thẩm định.');
        }
    }

    private function storeEvidence(UploadedFile $file): string
    {
        return $file->store('standard-hours/conversion-evidence', 'public');
    }

    private function deleteEvidence(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
