<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use App\Support\ManagerUnitScope;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\AcademicDepartment;
use Modules\StandardHours\Models\DepartmentOvertimePool;
use Modules\StandardHours\Services\DepartmentOvertimeService;
use Modules\StandardHours\Services\HourNormService;

class DepartmentOvertimeController extends StandardHoursBaseController
{
    public function __construct(
        private readonly DepartmentOvertimeService $service,
        private readonly HourNormService $hourNorms,
    ) {
        parent::__construct();
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))->only([
            'calculate', 'saveAllocations', 'lock', 'suggest',
        ]);
    }

    public function index(Request $request)
    {
        $years = $this->hourNorms->getYears();
        $year = $request->get('year') ?: ($years ? array_key_first($years) : null);
        $departments = AcademicDepartment::query()
            ->active()
            ->with('unit')
            ->withCount('instructors')
            ->when(ManagerUnitScope::isScoped(), fn ($q) => $q
                ->whereIn('unit_id', ManagerUnitScope::managedUnitIds()))
            ->orderBy('unit_id')
            ->orderBy('name')
            ->get();

        $pools = DepartmentOvertimePool::query()
            ->currentPeriod()
            ->when($year, fn ($q) => $q->where('year', $year))
            ->when(ManagerUnitScope::isScoped(), fn ($q) => $q
                ->whereHas('department', fn ($departmentQuery) => $departmentQuery
                    ->whereIn('unit_id', ManagerUnitScope::managedUnitIds())))
            ->with('department.unit')
            ->orderByDesc('calculated_at')
            ->get()
            ->keyBy('department_id');

        return view('standardhours::department-overtime.index', compact(
            'years', 'year', 'departments', 'pools'
        ));
    }

    public function show(Request $request, AcademicDepartment $department)
    {
        ManagerUnitScope::ensureCanAccessUnit((int) $department->unit_id);
        $years = $this->hourNorms->getYears();
        $year = $request->get('year') ?: ($years ? array_key_first($years) : null);
        $pool = $year
            ? DepartmentOvertimePool::query()
                ->currentPeriod()
                ->where('department_id', $department->id)
                ->where('year', $year)
                ->with(['allocations.instructor'])
                ->first()
            : null;

        $department->load(['unit', 'instructors' => fn ($q) => $q->orderBy('name')]);
        $suggest = $pool ? $this->service->suggestEqual($pool) : [];

        return view('standardhours::department-overtime.show', compact(
            'department', 'years', 'year', 'pool', 'suggest'
        ));
    }

    public function calculate(Request $request, AcademicDepartment $department)
    {
        ManagerUnitScope::ensureCanAccessUnit((int) $department->unit_id);
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2200',
        ]);

        try {
            $pool = $this->service->calculatePool($department, $data['year'], true);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.department-overtime.show', [
                'department' => $department,
                'year' => $data['year'],
            ])
            ->with('success', "Đã tính pool vượt: {$pool->pool_excess_hours} giờ (thực hiện {$pool->pool_done_hours} − định mức {$pool->pool_must_hours}).");
    }

    public function saveAllocations(Request $request, DepartmentOvertimePool $pool)
    {
        $pool->loadMissing('department');
        ManagerUnitScope::ensureCanAccessUnit((int) $pool->department->unit_id);
        $data = $request->validate([
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->saveAllocations(
                $pool,
                $data['allocations'] ?? [],
                $data['note'] ?? null
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Đã lưu phân bổ vượt định mức (công khai trong pool).');
    }

    public function lock(DepartmentOvertimePool $pool)
    {
        $pool->loadMissing('department');
        ManagerUnitScope::ensureCanAccessUnit((int) $pool->department->unit_id);
        $this->service->lock($pool);

        return back()->with('success', 'Đã khóa pool vượt định mức bộ môn.');
    }
}
