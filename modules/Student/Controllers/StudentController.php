<?php

namespace Modules\Student\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\Specialization\Models\Specialization;
use Modules\Student\Requests\CreateStudentRequest;
use Modules\Student\Requests\UpdateStudentRequest;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

class StudentController extends ModuleBaseController
{
    /**
     * Display a listing of students
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = User::with(['class'])
            ->where('user_type', 'student');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        // class_id / status: dùng has + !== '' vì filled('status') có thể bỏ sót "0" tùy input Tom Select
        if ($request->has('class_id') && $request->input('class_id') !== null && $request->input('class_id') !== '') {
            $query->where('class_id', (int) $request->input('class_id'));
        }

        if ($request->has('status') && $request->input('status') !== null && $request->input('status') !== '') {
            $query->where('status', (int) $request->input('status'));
        }

        // sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';

        // Get per page value (default 10, allow 5, 10, 15, 25, 50)
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $students = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->all());

        $classes = ClassModel::pluck('name', 'id');

        // Check if current user can delete students
        $canDelete = Auth::check() && Auth::user()->can('students.delete');

        // Data for student-only import modal (class setup)
        $units = Unit::orderBy('name')->pluck('name', 'id');
        $specializations = Specialization::query()
            ->with('trainingSystem:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->id => $item->selection_label]);
        $classrooms = Classroom::query()
            ->orderBy('name')
            ->pluck('name', 'id');
        $instructorsForImport = Instructor::query()
            ->with('unit:id,name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit_id']);

        return view('student::index', compact(
            'students',
            'classes',
            'canDelete',
            'units',
            'specializations',
            'classrooms',
            'instructorsForImport'
        ));
    }

    /**
     * Show the form for creating a new student
     */
    public function create()
    {
        // Permission already checked by middleware
        $classes = ClassModel::pluck('name', 'id');

        return view('student::create', compact('classes'));
    }

    /**
     * Store a newly created student
     */
    public function store(CreateStudentRequest $request)
    {
        // Permission already checked by middleware

        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        $data['user_type'] = 'student';
        $data['status'] = $data['status'] ?? 1;

        // Remove role_id from data to prevent default role assignment
        unset($data['role_id']);

        $student = User::create($data);

        // Assign student role if it exists
        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $student->syncRoles([$studentRole->name]);
            // Also update role_id in database for consistency
            $student->update(['role_id' => $studentRole->id]);
        }

        return redirect()->route('students.index')
            ->with('success', 'Học viên đã được tạo thành công!');
    }

    /**
     * Display the specified student
     */
    public function show(User $student)
    {
        // Permission already checked by middleware

        // Ensure this is a student
        if ($student->user_type !== 'student') {
            abort(404);
        }

        $student->load(['class']);

        return view('student::show', compact('student'));
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit(User $student)
    {
        // Permission already checked by middleware

        // Ensure this is a student
        if ($student->user_type !== 'student') {
            abort(404);
        }

        $classes = ClassModel::pluck('name', 'id');

        return view('student::edit', compact('student', 'classes'));
    }

    /**
     * Update the specified student
     */
    public function update(UpdateStudentRequest $request, User $student)
    {
        // Permission already checked by middleware

        // Ensure this is a student
        if ($student->user_type !== 'student') {
            abort(404);
        }

        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $data['user_type'] = 'student';
        $student->update($data);

        return redirect()->route('students.index')
            ->with('success', 'Học viên đã được cập nhật thành công!');
    }

    /**
     * Remove the specified student
     */
    public function destroy(User $student)
    {
        // Permission already checked by middleware

        // Ensure this is a student
        if ($student->user_type !== 'student') {
            abort(404);
        }

        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Học viên đã được xóa thành công!');
    }

    /**
     * Bulk delete multiple students
     */
    public function bulkDestroy(Request $request)
    {
        // Permission already checked by middleware

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ]);

        $studentIds = $request->student_ids;
        $currentUserId = Auth::id();

        // Filter out the current user ID and only delete students
        $studentsToDelete = User::whereIn('id', $studentIds)
            ->where('id', '!=', $currentUserId)
            ->where('user_type', 'student')
            ->get();

        if ($studentsToDelete->isEmpty()) {
            return redirect()->route('students.index')
                ->with('error', 'Không có học viên nào có thể xóa được từ danh sách đã chọn.');
        }

        $deletedCount = $studentsToDelete->count();
        $totalSelected = count($studentIds);

        // Delete the students
        User::whereIn('id', $studentsToDelete->pluck('id'))->delete();

        $message = "Đã xóa thành công {$deletedCount} học viên.";
        if ($deletedCount < $totalSelected) {
            $skipped = $totalSelected - $deletedCount;
            $message .= " Đã bỏ qua {$skipped} học viên không thể xóa.";
        }

        return redirect()->route('students.index')
            ->with('success', $message);
    }
}
