<?php

namespace Modules\Class\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;

class ClassController extends ModuleBaseController
{
    /**
     * Display a listing of the classes.
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = ClassModel::with(['specialization', 'instructor', 'classroom', 'creator', 'updater']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Hệ → Ngành → lớp
        if ($request->filled('training_system_id')) {
            $query->whereHas('specialization', fn ($q) => $q->where('training_system_id', $request->integer('training_system_id')));
        }

        if ($request->filled('specialization')) {
            $query->bySpecialization($request->specialization);
        }

        if ($request->filled('instructor')) {
            $query->byInstructor($request->instructor);
        }

        if ($request->filled('classroom')) {
            $query->where('classroom_id', $request->classroom);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 1);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get per page value (default 10, allow 5, 10, 15, 25, 50)
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        // Get paginated results
        $classes = $query->paginate($perPage)->withQueryString();

        // Get data for filters
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');
        $specQuery = Specialization::active()->with('trainingSystem')->orderBy('name');
        if ($request->filled('training_system_id')) {
            $specQuery->where('training_system_id', $request->integer('training_system_id'));
        }
        $specializations = $specQuery->get();
        $instructors = Instructor::active()->orderBy('name')->get();
        $classrooms = Classroom::where('status', true)->orderBy('name')->get();

        return view('class::index', compact('classes', 'trainingSystems', 'specializations', 'instructors', 'classrooms'));
    }

    /**
     * Show the form for creating a new class.
     */
    public function create()
    {
        // Permission already checked by middleware
        [$trainingSystems, $specializations, $specializationsBySystem] = $this->cascadeFormData();
        $instructors = Instructor::active()->orderBy('name')->get();
        $classrooms = Classroom::where('status', true)->orderBy('name')->get();

        return view('class::create', compact(
            'trainingSystems',
            'specializations',
            'specializationsBySystem',
            'instructors',
            'classrooms'
        ));
    }

    /**
     * Store a newly created class in storage.
     */
    public function store(Request $request)
    {
        // Permission already checked by middleware
        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:classes,code',
            'specialization_id' => 'required|exists:specializations,id',
            'instructor_id' => 'required|exists:instructors,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'duration_months' => 'required|integer|min:1',
            'management_unit' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'max_students' => 'required|integer|min:1',
            'current_students' => 'nullable|integer|min:0|lte:max_students',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Add user info
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        // Set default values
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['current_students'] = $validated['current_students'] ?? 0;

        // Create class
        $class = ClassModel::create($validated);
        app(LmsCourseProvisioningService::class)->provisionFromClassCurriculum($class, $request->user());

        return redirect()
            ->route('classes.index')
            ->with('success', 'Lớp học đã được tạo thành công.');
    }

    /**
     * Display the specified class.
     */
    public function show(ClassModel $class)
    {
        // Permission already checked by middleware

        $class->load(['specialization', 'instructor', 'classroom', 'creator', 'updater']);

        return view('class::show', compact('class'));
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClassModel $class)
    {
        // Permission already checked by middleware
        $class->load('specialization');
        [$trainingSystems, $specializations, $specializationsBySystem] = $this->cascadeFormData();
        $instructors = Instructor::active()->orderBy('name')->get();
        $classrooms = Classroom::where('status', true)->orderBy('name')->get();

        return view('class::edit', compact(
            'class',
            'trainingSystems',
            'specializations',
            'specializationsBySystem',
            'instructors',
            'classrooms'
        ));
    }

    /**
     * @return array{0:Collection,1:Collection,2:Collection}
     */
    protected function cascadeFormData(): array
    {
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');
        $specializationList = Specialization::active()
            ->with('trainingSystem:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'major_code', 'level', 'training_form', 'training_system_id']);
        $specializations = $specializationList;
        $specializationsBySystem = $specializationList
            ->groupBy(fn ($s) => (string) ($s->training_system_id ?? ''))
            ->map(fn ($items) => $items->mapWithKeys(fn ($s) => [(string) $s->id => $s->selection_label]));

        return [$trainingSystems, $specializations, $specializationsBySystem];
    }

    /**
     * Update the specified class in storage.
     */
    public function update(Request $request, ClassModel $class)
    {
        // Permission already checked by middleware
        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:classes,code,'.$class->id,
            'specialization_id' => 'required|exists:specializations,id',
            'instructor_id' => 'required|exists:instructors,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'duration_months' => 'required|integer|min:1',
            'management_unit' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'max_students' => 'required|integer|min:1',
            'current_students' => 'nullable|integer|min:0|lte:max_students',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Add user info
        $validated['updated_by'] = Auth::id();

        // Set default values
        $validated['is_active'] = $request->boolean('is_active');
        $validated['current_students'] = $validated['current_students'] ?? $class->current_students;

        // Update class
        $class->update($validated);
        app(LmsCourseProvisioningService::class)->provisionFromClassCurriculum($class->fresh(), $request->user());

        return redirect()
            ->route('classes.index')
            ->with('success', 'Lớp học đã được cập nhật thành công.');
    }

    /**
     * Remove the specified class from storage.
     */
    public function destroy(ClassModel $class)
    {
        // Permission already checked by middleware

        $class->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Lớp học đã được xóa thành công.');
    }
}
