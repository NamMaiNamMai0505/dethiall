<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Support\TrainingDept;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsSettings;
use Modules\Subject\Models\Subject;

class CourseController extends Controller
{
    public function __construct(
        protected LmsCourseService $service,
        protected LmsCourseProvisioningService $provisioning,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:lms.index')->only(['index', 'show']);
        $this->middleware('permission:lms.create')->only(['create', 'store', 'suggestInstructors', 'classStudentCount']);
        $this->middleware('permission:lms.edit')->only(['edit', 'update', 'syncMembers', 'syncContent']);
        $this->middleware('permission:lms.delete')->only(['destroy']);
    }

    public function index()
    {
        if (LmsAccess::usesLearnerShell()) {
            return redirect()->route('lms.learn.home');
        }

        $courses = $this->service->paginateForUser(15);

        return view('lms::courses.index', compact('courses'));
    }

    /**
     * Sprint 8 M1 — wizard 1 bước (view steps) → createWithMembers.
     */
    public function create()
    {
        $user = Auth::user();
        $subjectQuery = Subject::query()->active();
        TrainingDept::applySubjectFacultyScope($subjectQuery, $user);
        $subjects = $subjectQuery->orderBy('name')->get(['id', 'name', 'code']);
        $classes = ClassModel::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'specialization_id']);
        $instructors = Instructor::query()
            ->active()
            ->when(
                TrainingDept::isFacultyManager($user),
                fn ($query) => $query->where('unit_id', $user?->unit_id ?: -1)
            )
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'code']);
        $academicYears = AcademicYear::query()->where('is_active', true)->orderByDesc('start_year')->get(['id', 'code', 'name', 'is_current']);

        return view('lms::courses.create', compact('subjects', 'classes', 'instructors', 'academicYears'));
    }

    /** AJAX: gợi ý GV theo TeachingAssignment của môn */
    public function suggestInstructors(Request $request)
    {
        $subjectId = (int) $request->query('subject_id', 0);
        if ($subjectId <= 0) {
            return response()->json(['data' => []]);
        }
        $list = $this->service->suggestInstructorsForSubject($subjectId, $request->user());

        return response()->json(['data' => $list]);
    }

    /**
     * AJAX: đếm học viên sẽ được ghi danh nếu tạo khóa cho lớp này — Sprint 44
     * / C1. Dùng đúng điều kiện của LmsCourseProvisioningService::syncRoster()
     * (class_id + user_type student / role student) để con số khớp với thực
     * tế sau khi tạo khóa, không phải suy đoán từ current_students có thể lệch.
     */
    public function classStudentCount(Request $request)
    {
        $classId = (int) $request->query('class_id', 0);
        if ($classId <= 0) {
            return response()->json(['count' => 0]);
        }

        $count = \App\Models\User::query()
            ->where('class_id', $classId)
            ->where(function ($query) {
                $query->where('user_type', 'student')
                    ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'student'));
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:80',
            'section_code' => 'nullable|string|max:80',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'nullable|exists:classes,id',
            'is_standalone' => 'nullable|boolean',
            'instructor_id' => 'nullable|exists:instructors,id',
            // Bắt buộc: thiếu năm học thì báo cáo Dashboard và chuyển điểm sang
            // Quản lý điểm đều mất mốc thời gian.
            'academic_year_id' => 'required|exists:academic_years,id',
            'term' => 'nullable|in:semester_1,semester_2,semester_3,semester_4,semester_5,semester_6,summer',
            'description' => 'nullable|string|max:5000',
            'status' => 'nullable|in:draft,published,archived',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_standalone'] = $request->boolean('is_standalone');
        $data['status'] = $data['status']
            ?? LmsSettings::courseStatus();
        if (! $data['is_standalone'] && empty($data['class_id'])) {
            return back()->withErrors(['class_id' => 'Chọn lớp hoặc bật Lớp học phần độc lập.'])->withInput();
        }

        $this->assertCreateScope(
            $request,
            (int) $data['subject_id'],
            isset($data['instructor_id']) ? (int) $data['instructor_id'] : null
        );

        $course = $this->service->createWithMembers($data);

        return redirect()
            ->route('lms.courses.show', $course)
            ->with('success', 'Đã tạo khóa học LMS và đồng bộ thành viên từ lớp/giảng viên.');
    }

    public function show(LmsCourse $course)
    {
        $this->authorizeCourseView($course);

        if (LmsAccess::usesLearnerShell()) {
            return redirect()->route('lms.learn.courses.show', $course);
        }

        $course->load([
            'subject',
            'classModel',
            'instructor',
            'academicYear',
            'lessons.subjectLesson',
            'members.user',
        ]);

        return view('lms::courses.show', compact('course'));
    }

    public function edit(LmsCourse $course)
    {
        $this->authorizeCourseManage($course);
        $course->load(['subject', 'classModel', 'instructor']);
        $instructors = $this->service->suggestInstructorsForSubject((int) $course->subject_id, Auth::user());
        $academicYears = AcademicYear::query()->where('is_active', true)->orderByDesc('start_year')->get(['id', 'code', 'name', 'is_current']);

        return view('lms::courses.edit', compact('course', 'instructors', 'academicYears'));
    }

    public function update(Request $request, LmsCourse $course)
    {
        $this->authorizeCourseManage($course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'nullable|string|max:80',
            'instructor_id' => 'nullable|exists:instructors,id',
            // Bắt buộc: thiếu năm học thì báo cáo Dashboard và chuyển điểm sang
            // Quản lý điểm đều mất mốc thời gian.
            'academic_year_id' => 'required|exists:academic_years,id',
            'term' => 'nullable|in:semester_1,semester_2,semester_3,semester_4,semester_5,semester_6,summer',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:draft,published,archived',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'resync_members' => 'nullable|boolean',
        ]);
        $data['resync_members'] = $request->boolean('resync_members');

        $this->service->update($course, $data);

        return redirect()
            ->route('lms.courses.show', $course)
            ->with('success', 'Đã cập nhật khóa học LMS.');
    }

    public function syncMembers(LmsCourse $course)
    {
        $this->authorizeCourseManage($course);
        $this->service->syncMembersFromCore($course);

        return back()->with('success', 'Đã đồng bộ lại SV (theo lớp) và GV (theo phân công / GV khóa học).');
    }

    public function syncContent(LmsCourse $course)
    {
        $this->authorizeCourseManage($course);
        $content = $this->provisioning->syncCurriculumLessons($course);
        $attendance = $this->provisioning->syncAttendanceSessionsFromSchedule($course);

        return back()->with(
            'success',
            "Đã đồng bộ {$content['created']} bài mới, {$content['updated']} bài cập nhật và {$attendance['created']} buổi điểm danh mới."
        );
    }

    public function destroy(LmsCourse $course)
    {
        $this->authorizeCourseManage($course);
        $course->delete();

        return redirect()->route('lms.courses.index')->with('success', 'Đã xoá (soft) khóa học LMS.');
    }

    protected function authorizeCourseView(LmsCourse $course): void
    {
        $visible = $this->service->queryForUser()->where('lms_courses.id', $course->id)->exists();
        if (! $visible) {
            abort(403, 'Bạn không có quyền xem khóa học này.');
        }
    }

    protected function authorizeCourseManage(LmsCourse $course): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('lms.edit') && ! $user->can('lms.create'))) {
            abort(403);
        }
        $this->authorizeCourseView($course);
    }

    protected function assertCreateScope(Request $request, int $subjectId, ?int $instructorId): void
    {
        $user = $request->user();
        if (! $user || ! TrainingDept::isFacultyManager($user)) {
            return;
        }

        $subject = Subject::query()->findOrFail($subjectId);
        abort_unless(
            TrainingDept::subjectBelongsToFaculty($subject, $user),
            403,
            'Khoa chỉ được tạo lớp học phần cho môn thuộc đơn vị mình.'
        );

        if ($instructorId) {
            $inScope = Instructor::query()
                ->whereKey($instructorId)
                ->where('unit_id', $user->unit_id ?: -1)
                ->exists();
            abort_unless($inScope, 403, 'Giảng viên không thuộc đơn vị của bạn.');
        }
    }
}
