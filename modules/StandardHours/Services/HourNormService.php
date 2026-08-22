<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\StandardHours\Models\HourNorm;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;

class HourNormService
{
    public function __construct(
        private readonly PeriodService $periodService
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = HourNorm::with(['objectType', 'position', 'creator', 'updater'])
            ->currentPeriod();

        $query->byObjectType($filters['object_type_id'] ?? null);
        $query->byPosition($filters['position_id'] ?? null);
        $query->byYear($filters['year'] ?? null);

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'year';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): HourNorm
    {
        $data['period_mode'] = $this->periodService->mode();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return HourNorm::create($data);
    }

    public function update(HourNorm $hourNorm, array $data): HourNorm
    {
        $data['period_mode'] = $hourNorm->period_mode ?: $this->periodService->mode();
        $data['updated_by'] = Auth::id();
        $hourNorm->update($data);

        return $hourNorm->fresh(['objectType', 'position', 'creator', 'updater']);
    }

    public function delete(HourNorm $hourNorm): void
    {
        if ($this->isInUse($hourNorm)) {
            throw new \RuntimeException('Không thể xóa định mức đang được sử dụng.');
        }

        $hourNorm->delete();
    }

    public function toggleStatus(HourNorm $hourNorm): HourNorm
    {
        return $this->update($hourNorm, [
            'is_active' => ! $hourNorm->is_active,
        ]);
    }

    public function isInUse(HourNorm $hourNorm): bool
    {
        return false;
    }

    public function getFilterOptions(): array
    {
        return [
            'objectTypes' => ObjectType::active()->orderBy('name')->get(['id', 'name']),
            'positions' => Position::active()->orderBy('name')->get(['id', 'name', 'ratio_percent']),
            'years' => $this->getYears(),
        ];
    }

    public function getYears(): array
    {
        return $this->periodService->options();
    }

    public function getCurrentYear(): string
    {
        return (string) $this->periodService->currentYear();
    }
}
