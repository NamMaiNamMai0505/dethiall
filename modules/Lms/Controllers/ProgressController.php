<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsProgressService;
use Modules\Lms\Support\LmsAccess;

class ProgressController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsProgressService $progress,
    ) {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.progress', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);

        $summaries = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->with('user')
            ->orderByDesc('overall_pct')
            ->get();

        // Ensure all students appear
        $studentIds = $course->members()->where('role', LmsCourseMember::ROLE_STUDENT)->pluck('user_id');
        foreach ($studentIds as $uid) {
            if (! $summaries->firstWhere('user_id', $uid)) {
                $user = User::find($uid);
                if ($user) {
                    $summaries->push($this->progress->recompute($course, $user));
                }
            }
        }
        $summaries = $summaries->loadMissing('user')->sortByDesc('overall_pct')->values();

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::progress.index'
            : 'lms::learn.progress';

        return view($view, compact('course', 'summaries'));
    }

    /** JSON poll for realtime progress of current user */
    public function poll(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $summary = $this->progress->summaryFor($course, Auth::user());

        return response()->json([
            'overall_pct' => (float) $summary->overall_pct,
            'lessons' => [$summary->lessons_done, $summary->lessons_total],
            'materials' => [$summary->materials_done, $summary->materials_total],
            'assignments' => [$summary->assignments_done, $summary->assignments_total],
            'exams' => [$summary->exams_done, $summary->exams_total],
            'last_activity_at' => optional($summary->last_activity_at)->toIso8601String(),
        ]);
    }

    public function record(Request $request, LmsCourse $course)
    {
        $this->ensureVisible($course);
        $data = $request->validate([
            'trackable_type' => 'required|in:lesson,material,scorm,assignment,exam',
            'trackable_id' => 'nullable|integer',
            'event' => 'nullable|in:view,complete',
            'progress_pct' => 'nullable|integer|min:0|max:100',
        ]);

        $this->progress->record(
            $course,
            $data['trackable_type'],
            $data['trackable_id'] ?? null,
            $data['event'] ?? 'view',
            (int) ($data['progress_pct'] ?? ($data['event'] === 'complete' ? 100 : 50)),
        );

        return response()->json(['ok' => true]);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }
}
