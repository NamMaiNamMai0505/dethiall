<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\StandardHours\Models\ObjectType;

class ObjectTypeService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = ObjectType::with(['creator', 'updater']);

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

        $allowedSorts = ['code', 'name', 'created_at'];
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

    public function create(array $data): ObjectType
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return ObjectType::create($data);
    }

    public function update(ObjectType $objectType, array $data): ObjectType
    {
        $data['updated_by'] = Auth::id();
        $objectType->update($data);

        return $objectType->fresh(['creator', 'updater']);
    }

    public function delete(ObjectType $objectType): void
    {
        if ($this->isInUse($objectType)) {
            throw new \RuntimeException('Không thể xóa đối tượng đang được sử dụng.');
        }

        $objectType->delete();
    }

    public function toggleStatus(ObjectType $objectType): ObjectType
    {
        return $this->update($objectType, [
            'is_active' => ! $objectType->is_active,
        ]);
    }

    public function isInUse(ObjectType $objectType): bool
    {
        return $objectType->instructors()->exists()
            || \Modules\StandardHours\Models\YearlyResult::query()
                ->where('object_type_id', $objectType->id)
                ->exists();
    }
}
