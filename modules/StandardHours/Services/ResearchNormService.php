<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\ResearchNorm;

class ResearchNormService
{
    public function __construct(
        private readonly HourNormService $hourNormService
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = ResearchNorm::with(['objectType', 'creator', 'updater'])
            ->currentPeriod();

        $query->byObjectType($filters['object_type_id'] ?? null);
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

    public function create(array $data): ResearchNorm
    {
        $data['period_mode'] = app(PeriodService::class)->mode();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return ResearchNorm::create($data);
    }

    public function update(ResearchNorm $researchNorm, array $data): ResearchNorm
    {
        $data['period_mode'] = $researchNorm->period_mode ?: app(PeriodService::class)->mode();
        $data['updated_by'] = Auth::id();
        $researchNorm->update($data);

        return $researchNorm->fresh(['objectType', 'creator', 'updater']);
    }

    public function delete(ResearchNorm $researchNorm): void
    {
        if ($this->isInUse($researchNorm)) {
            throw new \RuntimeException('Không thể xóa định mức NCKH đang được sử dụng.');
        }

        $researchNorm->delete();
    }

    public function toggleStatus(ResearchNorm $researchNorm): ResearchNorm
    {
        return $this->update($researchNorm, [
            'is_active' => ! $researchNorm->is_active,
        ]);
    }

    public function isInUse(ResearchNorm $researchNorm): bool
    {
        return false;
    }

    public function getFilterOptions(): array
    {
        return [
            'objectTypes' => ObjectType::active()->orderBy('name')->get(['id', 'name']),
            'years' => $this->hourNormService->getYears(),
        ];
    }

    public function getCurrentYear(): string
    {
        return $this->hourNormService->getCurrentYear();
    }
}
