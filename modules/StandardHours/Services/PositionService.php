<?php

namespace Modules\StandardHours\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\StandardHours\Models\Position;

class PositionService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Position::with(['creator', 'updater']);

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

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [5, 10, 15, 25, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Position
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return Position::create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $data['updated_by'] = Auth::id();
        $position->update($data);

        return $position->fresh(['creator', 'updater']);
    }

    public function delete(Position $position): void
    {
        if ($this->isInUse($position)) {
            throw new \RuntimeException('Không thể xóa chức danh đang được sử dụng.');
        }

        $position->delete();
    }

    public function toggleStatus(Position $position): Position
    {
        return $this->update($position, [
            'is_active' => ! $position->is_active,
        ]);
    }

    public function isInUse(Position $position): bool
    {
        return $position->instructors()->exists()
            || \Modules\StandardHours\Models\YearlyResult::query()
                ->where('position_id', $position->id)
                ->exists()
            || \App\Models\User::query()->where('position_id', $position->id)->exists();
    }
}
