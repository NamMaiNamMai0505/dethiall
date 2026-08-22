<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

/**
 * Portal giảng viên — /lms/gv
 * Sprint GV-1: danh sách khóa dạy + badge.
 */
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:lms.index']);
    }

    public function index(LmsCourseService $service)
    {
        $user = Auth::user();

        // HV không vào portal dạy
        if (LmsAccess::isStudentUser($user) && ! LmsAccess::usesAdminShell($user)) {
            return redirect()->route('lms.learn.home')
                ->with('warning', 'Cổng giảng viên chỉ dành cho tài khoản giảng dạy.');
        }

        // Admin preview vẫn được xem
        $courses = $service->queryForUser($user)
            ->with(['subject', 'classModel', 'instructor'])
            ->withCount([
                'lessons',
                'members as students_count' => fn ($q) => $q->where('role', LmsCourseMember::ROLE_STUDENT),
            ])
            ->orderByDesc('id')
            ->paginate(12);

        $courseIds = collect($courses->items())->pluck('id');

        $pendingByCourse = [];
        $openExamsByCourse = [];

        if ($courseIds->isNotEmpty()) {
            // Số bài nộp chưa chấm (status submitted hoặc score null)
            $pending = LmsAssignmentSubmission::query()
                ->select('lms_assignments.lms_course_id', DB::raw('COUNT(*) as cnt'))
                ->join('lms_assignments', 'lms_assignments.id', '=', 'lms_assignment_submissions.lms_assignment_id')
                ->whereIn('lms_assignments.lms_course_id', $courseIds)
                ->where(function ($q) {
                    $q->whereNull('lms_assignment_submissions.score')
                        ->orWhereIn('lms_assignment_submissions.status', ['submitted', 'pending']);
                })
                ->whereNotNull('lms_assignment_submissions.submitted_at')
                ->groupBy('lms_assignments.lms_course_id')
                ->pluck('cnt', 'lms_course_id');

            $pendingByCourse = $pending->all();

            $openExams = LmsExam::query()
                ->whereIn('lms_course_id', $courseIds)
                ->where('is_published', true)
                ->where(function ($q) {
                    $q->whereNull('closes_at')->orWhere('closes_at', '>=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('opens_at')->orWhere('opens_at', '<=', now());
                })
                ->select('lms_course_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('lms_course_id')
                ->pluck('cnt', 'lms_course_id');

            $openExamsByCourse = $openExams->all();
        }

        return view('lms::teach.home', [
            'user' => $user,
            'courses' => $courses,
            'pendingByCourse' => $pendingByCourse,
            'openExamsByCourse' => $openExamsByCourse,
            'isAdminPreview' => LmsAccess::usesAdminShell($user),
        ]);
    }
}
