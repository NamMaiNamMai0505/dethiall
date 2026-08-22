<?php

namespace Modules\Lms\Controllers\Learn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCertificate;
use Modules\Lms\Models\LmsChatMessage;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsForumTopic;
use Modules\Lms\Models\LmsLearningAlert;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Models\LmsQuestionBank;
use Modules\Lms\Models\LmsScormPackage;
use Modules\Lms\Models\LmsSurvey;
use Modules\Lms\Models\LmsSurveyResponse;
use Modules\Lms\Models\LmsSurveyTemplate;
use Modules\Lms\Services\LmsCertificateService;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsGradebookService;
use Modules\Lms\Services\LmsProgressService;
use Modules\Lms\Services\LmsScormService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsCampus;
use Modules\Subject\Models\SubjectLesson;

class CourseRoomController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsProgressService $progress,
        protected LmsGradebookService $gradebook,
        protected LmsCertificateService $certs,
    ) {
        $this->middleware(['auth', 'permission:lms.index']);
    }

    public function show(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $user = Auth::user();
        $teachMode = LmsAccess::isTeachMode() && LmsAccess::canTeachCourse($course, $user);
        $isAdmin = LmsAccess::usesAdminShell() || $teachMode;

        $course->load([
            'subject',
            'classModel',
            'instructor',
            'lessons' => function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('is_published', true);
                }
                $q->with('subjectLesson');
            },
            'materials' => fn ($q) => $isAdmin ? $q : $q->where('is_published', true),
            'scormPackages' => fn ($q) => $isAdmin ? $q : $q->where('is_published', true),
        ]);

        $assignments = LmsAssignment::query()
            ->where('lms_course_id', $course->id)
            ->when(! $isAdmin, fn ($q) => $q->where('is_published', true))
            ->with('lesson')
            ->orderByDesc('id')
            ->get();

        $mySubs = LmsAssignmentSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_assignment_id', $assignments->pluck('id'))
            ->with('versions')
            ->get()
            ->keyBy('lms_assignment_id');

        $exams = LmsExam::query()
            ->where('lms_course_id', $course->id)
            ->when(! $isAdmin, fn ($q) => $q->where('is_published', true))
            ->withCount(['questions', 'attempts'])
            ->orderByDesc('id')
            ->get();

        $questionBanks = collect();

        $myExamAttempts = LmsExamAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_exam_id', $exams->pluck('id'))
            ->whereIn('status', ['submitted', 'graded'])
            ->orderByDesc('score')
            ->get()
            ->groupBy('lms_exam_id');

        $sessions = LmsAttendanceSession::query()
            ->where('lms_course_id', $course->id)
            ->with('scheduleDetail:id,date,period,subject_lesson_id')
            ->orderBy('session_date')
            ->get();

        $myAttendance = LmsAttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_attendance_session_id', $sessions->pluck('id'))
            ->get()
            ->keyBy('lms_attendance_session_id');

        $attendanceByDate = [];
        foreach ($sessions as $s) {
            $key = optional($s->session_date)->format('Y-m-d');
            if (! $key) {
                continue;
            }
            $rec = $myAttendance[$s->id] ?? null;
            $attendanceByDate[$key] = [
                'session_id' => $s->id,
                'title' => $s->title,
                'mode' => $s->mode,
                'status' => $s->status,
                'my_status' => $rec?->status,
                'my_status_label' => $rec?->statusLabel(),
                'token' => $s->checkin_token,
                'token_expires_at' => $s->token_expires_at?->toIso8601String(),
                'token_expired' => $s->isTokenExpired(),
                'open' => $s->isOpen(),
                'can_checkin' => $s->allowsSelfCheckin() && ! $rec,
                // Sprint 9 H2 — chi tiết IP/mạng cho HV
                'method' => $rec?->method,
                'client_ip' => $rec?->client_ip,
                'network_ok' => $rec?->network_ok,
                'network_note' => $rec?->network_note,
                'checked_in_at' => $rec?->checked_in_at?->format('d/m/Y H:i:s'),
                'note' => $rec?->note,
            ];
        }

        $progressSummary = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        if (! $progressSummary) {
            try {
                $progressSummary = $this->progress->recompute($course, $user);
            } catch (\Throwable $e) {
                $progressSummary = null;
            }
        }

        $alerts = LmsLearningAlert::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereNull('resolved_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $gradeMatrix = $this->gradebook->matrix($course, $isAdmin ? null : $user->id);
        $myGrade = $gradeMatrix['rows'][$user->id] ?? null;

        $certificate = LmsCertificate::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();
        $certEligibility = $this->certs->evaluateEligibility($course, $user);

        $surveys = LmsSurvey::query()
            ->where('lms_course_id', $course->id)
            ->when(! $teachMode && ! LmsAccess::usesAdminShell(), fn ($q) => $q->where('is_published', true))
            ->with('questions')
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();
        $mySurveyResponses = LmsSurveyResponse::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_survey_id', $surveys->pluck('id'))
            ->get()
            ->keyBy('lms_survey_id');

        $forumTopics = LmsForumTopic::query()
            ->where('lms_course_id', $course->id)
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $chatMessages = LmsChatMessage::query()
            ->where('lms_course_id', $course->id)
            ->whereNull('recipient_user_id')
            ->with('author:id,name')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values();

        $chatMembers = LmsCourseMember::query()
            ->where('lms_course_id', $course->id)
            ->where('status', LmsCourseMember::STATUS_ACTIVE)
            ->where('user_id', '!=', $user->id)
            ->with('user:id,name,email')
            ->orderBy('role')
            ->get()
            ->filter(fn ($m) => $m->user)
            ->values();

        $canModerateChat = LmsAccess::canTeachCourse($course, $user)
            || LmsAccess::usesAdminShell()
            || $user?->can('lms.edit');

        $canTeach = LmsAccess::canTeachCourse($course, $user);

        $campus = LmsCampus::meta();

        $tab = request('tab', 'overview');
        $pendingOnly = request()->boolean('pending_only');

        // GV-3: submissions for grading board
        $teachSubmissions = collect();
        // GV-5: roster + stats
        $teachStudents = collect();
        $teachSession = null;
        $teachSessionRecords = collect();
        $teachAttendanceStats = [];
        $classProgress = collect();
        $classAlerts = collect();
        $classCerts = collect();
        $classCertEligibility = [];
        $classSurveyStats = [];
        // Sprint 8 G8: map CTĐT
        $subjectLessons = collect();
        // Sprint 9 M6
        $surveyTemplates = collect();

        if ($teachMode) {
            if ($course->subject_id) {
                $subjectLessons = SubjectLesson::query()
                    ->where('subject_id', $course->subject_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'code', 'name', 'lesson_kind', 'sort_order', 'parent_id']);
            }
            if (Schema::hasTable('lms_survey_templates')) {
                $surveyTemplates = LmsSurveyTemplate::query()
                    ->where('is_active', true)
                    ->withCount('questions')
                    ->orderBy('title')
                    ->get();
            }
            $questionBanks = LmsQuestionBank::query()
                ->where('lms_course_id', $course->id)
                ->with(['questions' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->withCount('questions')
                ->orderByDesc('id')
                ->get();

            // Load all exams (incl. draft) for teach mode
            $exams = LmsExam::query()
                ->where('lms_course_id', $course->id)
                ->withCount(['questions', 'attempts'])
                ->orderByDesc('id')
                ->get();

            // Sprint 8 G7: filter ?pending_only=1 → chỉ bài chờ chấm
            $teachSubmissions = LmsAssignmentSubmission::query()
                ->whereIn('lms_assignment_id', $assignments->pluck('id'))
                ->when($pendingOnly, fn ($q) => $q->where('status', 'submitted'))
                ->with(['user:id,name,email', 'assignment'])
                ->orderByDesc('submitted_at')
                ->get()
                ->groupBy('lms_assignment_id');

            $teachStudents = $course->members()
                ->where('role', LmsCourseMember::ROLE_STUDENT)
                ->with('user:id,name,email')
                ->get()
                ->filter(fn ($m) => $m->user)
                ->values();

            $sessions->load('records');
            $sessionId = (int) request('session', 0);
            $teachSession = $sessionId
                ? $sessions->firstWhere('id', $sessionId)
                : ($sessions->firstWhere('status', 'open') ?: $sessions->sortByDesc('session_date')->first());

            if ($teachSession) {
                $teachSessionRecords = $teachSession->records->keyBy('user_id');
            }

            // % chuyên cần: present+late / total sessions
            $attendanceSessionsForStats = $sessions->filter(
                fn ($session) => ! $session->session_date || $session->session_date->lte(today())
            );
            foreach ($teachStudents as $m) {
                $uid = $m->user_id;
                $present = 0;
                foreach ($attendanceSessionsForStats as $s) {
                    $rec = $s->records->firstWhere('user_id', $uid);
                    if ($rec && in_array($rec->status, ['present', 'late'], true)) {
                        $present++;
                    }
                }
                $teachAttendanceStats[$uid] = [
                    'present' => $present,
                    'total' => $attendanceSessionsForStats->count(),
                    'pct' => $attendanceSessionsForStats->count() > 0 ? round($present / $attendanceSessionsForStats->count() * 100, 1) : 0,
                ];
            }

            // GV-6: progress + alerts + certs + survey avgs for class
            $studentIds = $teachStudents->pluck('user_id');
            $classProgress = LmsProgressSummary::query()
                ->where('lms_course_id', $course->id)
                ->whereIn('user_id', $studentIds)
                ->get()
                ->keyBy('user_id');
            $classAlerts = LmsLearningAlert::query()
                ->where('lms_course_id', $course->id)
                ->whereIn('user_id', $studentIds)
                ->whereNull('resolved_at')
                ->orderByDesc('id')
                ->limit(50)
                ->with('user:id,name')
                ->get();
            $classCerts = LmsCertificate::query()
                ->where('lms_course_id', $course->id)
                ->whereIn('user_id', $studentIds)
                ->get()
                ->keyBy('user_id');

            $classCertEligibility = $this->certs->evaluateMany($course, $teachStudents->pluck('user')->filter()->values());

            // Surveys with response counts for teach manage
            $surveys = LmsSurvey::query()
                ->where('lms_course_id', $course->id)
                ->with('questions')
                ->withCount(['questions', 'responses'])
                ->orderByDesc('id')
                ->get();

            foreach ($surveys as $sv) {
                $resps = $sv->responses()->get();
                $ratings = [];
                foreach ($sv->questions as $q) {
                    if ($q->type !== 'rating_1_5') {
                        continue;
                    }
                    $vals = [];
                    foreach ($resps as $r) {
                        $ans = $r->answers[(string) $q->id] ?? $r->answers[$q->id] ?? null;
                        if ($ans !== null && is_numeric($ans)) {
                            $vals[] = (float) $ans;
                        }
                    }
                    if ($vals) {
                        $ratings[] = [
                            'stem' => $q->stem,
                            'avg' => round(array_sum($vals) / count($vals), 2),
                            'n' => count($vals),
                        ];
                    }
                }
                $classSurveyStats[] = [
                    'title' => $sv->title,
                    'responses' => $resps->count(),
                    'ratings' => $ratings,
                ];
            }

            // Ensure grade matrix includes full class for teach view
            if (empty($gradeMatrix['rows'])) {
                try {
                    $gradeMatrix = $this->gradebook->matrix($course);
                } catch (\Throwable $e) {
                }
            }
        }

        return view('lms::learn.course', compact(
            'course',
            'assignments',
            'mySubs',
            'exams',
            'questionBanks',
            'myExamAttempts',
            'sessions',
            'attendanceByDate',
            'progressSummary',
            'alerts',
            'myGrade',
            'gradeMatrix',
            'certificate',
            'certEligibility',
            'surveys',
            'mySurveyResponses',
            'forumTopics',
            'chatMessages',
            'chatMembers',
            'canModerateChat',
            'canTeach',
            'teachMode',
            'teachSubmissions',
            'teachStudents',
            'teachSession',
            'teachSessionRecords',
            'teachAttendanceStats',
            'classProgress',
            'classAlerts',
            'classCerts',
            'classCertEligibility',
            'classSurveyStats',
            'campus',
            'tab',
            'pendingOnly',
            'subjectLessons',
            'surveyTemplates',
        ));
    }

    public function lesson(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureVisible($course);
        $isAdmin = LmsAccess::usesAdminShell();
        if ((int) $lesson->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        if (! $lesson->is_published && ! $isAdmin) {
            abort(404);
        }

        $materials = LmsMaterial::query()
            ->where('lms_course_id', $course->id)
            ->where('is_published', true)
            ->where(function ($q) use ($lesson) {
                $q->where('lms_lesson_id', $lesson->id)->orWhereNull('lms_lesson_id');
            })
            ->orderBy('sort_order')
            ->get();

        try {
            $this->progress->record($course, 'lesson', $lesson->id, 'view', 50);
        } catch (\Throwable $e) {
        }

        return view('lms::learn.lesson', compact('course', 'lesson', 'materials'));
    }

    public function material(LmsCourse $course, LmsMaterial $material)
    {
        $this->ensureVisible($course);
        $isAdmin = LmsAccess::usesAdminShell();
        if ((int) $material->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        if (! $material->is_published && ! $isAdmin) {
            abort(404);
        }

        $url = $material->url();
        if (! $url) {
            return back()->with('error', 'Không tìm thấy file.');
        }

        try {
            $this->progress->record($course, 'material', $material->id, 'view', 80);
        } catch (\Throwable $e) {
        }

        return view('lms::learn.material-view', compact('course', 'material', 'url'));
    }

    public function scorm(LmsCourse $course, LmsScormPackage $scorm)
    {
        $this->ensureVisible($course);
        $isAdmin = LmsAccess::usesAdminShell();
        if ((int) $scorm->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        if (! $scorm->is_published && ! $isAdmin) {
            abort(404);
        }

        $attempt = app(LmsScormService::class)->getOrStart($course, $scorm);

        try {
            $this->progress->record($course, 'scorm', $scorm->id, 'view', 10);
        } catch (\Throwable $e) {
        }

        return view('lms::learn.scorm', compact('course', 'scorm', 'attempt'));
    }

    /** Sprint 9 T3 — SCORM runtime commit */
    public function scormCommit(Request $request, LmsCourse $course, LmsScormPackage $scorm)
    {
        $this->ensureVisible($course);
        if ((int) $scorm->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $cmi = $request->input('cmi', []);
        if (! is_array($cmi)) {
            $cmi = [];
        }
        $attempt = app(LmsScormService::class)->commit($course, $scorm, $cmi);

        return response()->json([
            'ok' => true,
            'attempt' => [
                'lesson_status' => $attempt->lesson_status,
                'score_raw' => $attempt->score_raw,
                'score_max' => $attempt->score_max,
                'total_time_sec' => $attempt->total_time_sec,
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ],
        ]);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403, 'Bạn không thuộc khóa học này.');
        }
    }
}
