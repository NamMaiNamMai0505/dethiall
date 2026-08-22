<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\StandardHours\Models\ResearchCategory;

class ResearchCategoryService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = ResearchCategory::with(['creator', 'updater']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $allowedSorts = ['code', 'created_at'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): ResearchCategory
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return ResearchCategory::create($data);
    }

    public function update(ResearchCategory $category, array $data): ResearchCategory
    {
        $data['updated_by'] = Auth::id();
        $category->update($data);

        return $category->fresh(['creator', 'updater']);
    }

    public function delete(ResearchCategory $category): void
    {
        if ($this->isInUse($category)) {
            throw new \RuntimeException('Không thể xóa danh mục NCKH đang được sử dụng.');
        }

        $category->delete();
    }

    public function toggleStatus(ResearchCategory $category): ResearchCategory
    {
        $category->update([
            'is_active' => ! $category->is_active,
            'updated_by' => Auth::id(),
        ]);

        return $category->fresh(['creator', 'updater']);
    }

    public function isInUse(ResearchCategory $category): bool
    {
        return $category->researchRecords()->exists();
    }
}
