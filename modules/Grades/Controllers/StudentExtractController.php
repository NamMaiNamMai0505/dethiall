<?php

namespace Modules\Grades\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\StudentProfile;
use Modules\Grades\Services\GradeAccess;
use Modules\Grades\Services\GradeActor;

class StudentExtractController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);

        $classId = $request->integer('class_id') ?: null;
        $q = User::query()
            ->where('user_type', 'student')
            ->with(['class'])
            ->orderBy('name');

        if (GradeActor::isInstructorScoped($user) && $user->instructor_id) {
            $classIds = GradeAccess::teachingPairs($user)->pluck('class_id')->unique()->filter()->all();
            $q->whereIn('class_id', $classIds ?: [0]);
        } elseif ($classId) {
            $q->where('class_id', $classId);
        }

        $students = $q->paginate(40)->withQueryString();
        $profiles = StudentProfile::query()
            ->whereIn('user_id', $students->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $classes = ClassModel::query()->orderBy('name')->pluck('name', 'id');
        $canEdit = GradeActor::canEditOperational($user) || GradeActor::canFull($user);

        return view('grades::academic.extracts.index', compact(
            'students', 'profiles', 'classes', 'classId', 'canEdit'
        ));
    }

    public function edit(User $student)
    {
        $user = Auth::user();
        abort_unless(GradeAccess::canEnter($user), 403);
        abort_unless($student->user_type === 'student', 404);

        if (GradeActor::isInstructorScoped($user)) {
            $classIds = GradeAccess::teachingPairs($user)->pluck('class_id')->all();
            abort_unless(in_array((int) $student->class_id, $classIds, true), 403);
        }

        $profile = StudentProfile::query()->firstOrNew(['user_id' => $student->id]);
        $canEdit = GradeActor::canEditOperational($user) || GradeActor::canFull($user);

        return view('grades::academic.extracts.edit', compact('student', 'profile', 'canEdit'));
    }

    public function update(Request $request, User $student)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canEditOperational($user) || GradeActor::canFull($user), 403);
        abort_unless($student->user_type === 'student', 404);

        $data = $request->validate([
            'student_code' => 'nullable|string|max:50',
            'birth_place' => 'nullable|string|max:255',
            'ethnicity' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:30',
            'id_issued_at' => 'nullable|date',
            'id_issued_place' => 'nullable|string|max:255',
            'permanent_address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);

        StudentProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [...$data, 'updated_by' => $user->id]
        );

        return back()->with('success', 'Đã cập nhật hồ sơ trích ngang.');
    }

    public function print(Request $request)
    {
        $user = Auth::user();
        abort_unless(GradeActor::canPrint($user), 403);

        $classId = $request->integer('class_id') ?: null;
        $q = User::query()->where('user_type', 'student')->with('class')->orderBy('name');
        if (GradeActor::isInstructorScoped($user) && $user->instructor_id) {
            $classIds = GradeAccess::teachingPairs($user)->pluck('class_id')->unique()->filter()->all();
            $q->whereIn('class_id', $classIds ?: [0]);
        } elseif ($classId) {
            $q->where('class_id', $classId);
        }
        $students = $q->get();
        $profiles = StudentProfile::query()->whereIn('user_id', $students->pluck('id'))->get()->keyBy('user_id');
        $class = $classId ? ClassModel::query()->find($classId) : null;

        return view('grades::academic.extracts.print', compact('students', 'profiles', 'class'));
    }
}
