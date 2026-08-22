<?php

namespace Modules\StandardHours\Services;

use App\Support\ManagerUnitScope;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\InstructorNormReduction;

class NormReductionService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = InstructorNormReduction::query()
            ->with(['instructor.unit', 'creator'])
            ->currentPeriod()
            ->orderByDesc('id');
        if (ManagerUnitScope::isScoped()) {
            $query->whereHas('instructor', fn ($q) => $q
                ->whereIn('unit_id', ManagerUnitScope::managedUnitIds()));
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        if (! empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    public function create(array $data): InstructorNormReduction
    {
        ManagerUnitScope::ensureCanAccessInstructor((int) $data['instructor_id']);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['period_mode'] = app(PeriodService::class)->mode();
        $data['is_active'] = $data['is_active'] ?? true;

        if (empty($data['days']) && ! empty($data['start_date']) && ! empty($data['end_date'])) {
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $data['days'] = max(0, $start->diffInDays($end) + 1);
        }

        return InstructorNormReduction::create($data);
    }

    public function update(InstructorNormReduction $row, array $data): InstructorNormReduction
    {
        ManagerUnitScope::ensureCanAccessInstructor($row->instructor_id);
        ManagerUnitScope::ensureCanAccessInstructor((int) ($data['instructor_id'] ?? $row->instructor_id));
        $data['updated_by'] = Auth::id();
        $data['period_mode'] = $row->period_mode ?: app(PeriodService::class)->mode();
        if (empty($data['days']) && ! empty($data['start_date']) && ! empty($data['end_date'])) {
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $data['days'] = max(0, $start->diffInDays($end) + 1);
        }
        $row->update($data);

        return $row->fresh(['instructor']);
    }

    public function delete(InstructorNormReduction $row): void
    {
        ManagerUnitScope::ensureCanAccessInstructor($row->instructor_id);
        $row->delete();
    }

    public function instructors()
    {
        $query = Instructor::active()->orderBy('name');
        ManagerUnitScope::applyToInstructorQuery($query);

        return $query->get(['id', 'name', 'code', 'unit_id']);
    }
}
