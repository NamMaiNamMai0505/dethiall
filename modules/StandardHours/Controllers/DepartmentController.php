<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\AcademicDepartment;
use Modules\StandardHours\Services\DepartmentService;

class DepartmentController extends StandardHoursBaseController
{
    public function __construct(private readonly DepartmentService $service)
    {
        parent::__construct();
        // store/update/destroy do base controller gác; gán giảng viên = quyền Sửa.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))->only(['syncInstructors']);
    }

    public function index(Request $request)
    {
        $departments = $this->service->paginate($request->all());
        $units = $this->service->unitOptions();

        return view('standardhours::departments.index', compact('departments', 'units'));
    }

    public function create()
    {
        $units = $this->service->unitOptions();

        return view('standardhours::departments.create', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $dept = $this->service->create($data);

        return redirect()
            ->route('standard-hours.departments.show', $dept)
            ->with('success', 'Đã tạo bộ môn.');
    }

    public function show(AcademicDepartment $department)
    {
        $this->service->ensureCanAccess($department);
        $department->load(['unit', 'instructors' => fn ($q) => $q->orderBy('name')]);
        $candidates = $this->service->instructorsForUnit((int) $department->unit_id);

        return view('standardhours::departments.show', compact('department', 'candidates'));
    }

    public function edit(AcademicDepartment $department)
    {
        $this->service->ensureCanAccess($department);
        $units = $this->service->unitOptions();

        return view('standardhours::departments.edit', compact('department', 'units'));
    }

    public function update(Request $request, AcademicDepartment $department)
    {
        $data = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->update($department, $data);

        return redirect()
            ->route('standard-hours.departments.show', $department)
            ->with('success', 'Đã cập nhật bộ môn.');
    }

    public function destroy(AcademicDepartment $department)
    {
        $this->service->delete($department);

        return redirect()
            ->route('standard-hours.departments.index')
            ->with('success', 'Đã xóa bộ môn (đã gỡ gán GV).');
    }

    public function syncInstructors(Request $request, AcademicDepartment $department)
    {
        $data = $request->validate([
            'instructor_ids' => 'nullable|array',
            'instructor_ids.*' => 'integer|exists:instructors,id',
        ]);
        $this->service->syncInstructors($department, $data['instructor_ids'] ?? []);

        return back()->with('success', 'Đã cập nhật danh sách GV thuộc bộ môn.');
    }
}
