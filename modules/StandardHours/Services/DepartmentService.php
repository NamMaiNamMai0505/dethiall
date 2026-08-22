<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\AcademicDepartment;
use Modules\Unit\Models\Unit;

class DepartmentService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = AcademicDepartment::query()
            ->with(['unit', 'instructors'])
            ->withCount('instructors');
        if (ManagerUnitScope::isScoped()) {
            $query->whereIn('unit_id', ManagerUnitScope::managedUnitIds());
        }

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (($filters['sort_by'] ?? null) === 'code') {
            $sortOrder = ($filters['sort_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy('code', $sortOrder);
        } else {
            $query->orderBy('unit_id')->orderBy('sort_order')->orderBy('name');
        }

        return $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function create(array $data): AcademicDepartment
    {
        ManagerUnitScope::ensureCanAccessUnit((int) $data['unit_id']);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['is_active'] = $data['is_active'] ?? true;

        return AcademicDepartment::create($data);
    }

    public function update(AcademicDepartment $dept, array $data): AcademicDepartment
    {
        $this->ensureCanAccess($dept);
        ManagerUnitScope::ensureCanAccessUnit((int) ($data['unit_id'] ?? $dept->unit_id));
        $data['updated_by'] = Auth::id();
        $dept->update($data);

        return $dept->fresh(['unit', 'instructors']);
    }

    public function delete(AcademicDepartment $dept): void
    {
        $this->ensureCanAccess($dept);
        if ($dept->instructors()->exists()) {
            // Gỡ gán trước
            $dept->instructors()->update(['department_id' => null]);
        }
        $dept->delete();
    }

    /**
     * Gán danh sách instructor_ids vào bộ môn (chỉ GV cùng unit).
     *
     * @param  list<int>  $instructorIds
     */
    public function syncInstructors(AcademicDepartment $dept, array $instructorIds): void
    {
        $this->ensureCanAccess($dept);
        $outsideCount = Instructor::query()
            ->whereIn('id', $instructorIds ?: [0])
            ->where('unit_id', '!=', $dept->unit_id)
            ->count();
        if ($outsideCount > 0) {
            throw new \RuntimeException('Chỉ được gán giảng viên cùng đơn vị với bộ môn.');
        }

        DB::transaction(function () use ($dept, $instructorIds) {
            // Gỡ GV cũ của BM này không còn trong list
            Instructor::query()
                ->where('department_id', $dept->id)
                ->whereNotIn('id', $instructorIds ?: [0])
                ->update(['department_id' => null]);

            if ($instructorIds === []) {
                return;
            }

            Instructor::query()
                ->where('unit_id', $dept->unit_id)
                ->whereIn('id', $instructorIds)
                ->update(['department_id' => $dept->id]);
        });
    }

    public function unitOptions()
    {
        $query = Unit::query()->where('status', Unit::STATUS_ACTIVE);
        if (ManagerUnitScope::isScoped()) {
            $query->whereIn('id', ManagerUnitScope::managedUnitIds());
        }

        return $query->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function instructorsForUnit(int $unitId)
    {
        ManagerUnitScope::ensureCanAccessUnit($unitId);

        return Instructor::active()
            ->where('unit_id', $unitId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id', 'unit_id']);
    }

    public function ensureCanAccess(AcademicDepartment $department): void
    {
        ManagerUnitScope::ensureCanAccessUnit((int) $department->unit_id);
    }
}
