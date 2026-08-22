<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsLearningAlert;
use Modules\Lms\Services\LmsAlertService;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class AlertController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsAlertService $alerts,
    ) {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.alerts', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);

        $query = LmsLearningAlert::query()
            ->where('lms_course_id', $course->id)
            ->with('user')
            ->orderByDesc('id');

        if (LmsAccess::usesLearnerShell() && ! Auth::user()?->can('lms.edit')) {
            $query->where('user_id', Auth::id());
        }

        $alerts = $query->paginate(30);

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::alerts.index'
            : 'lms::learn.alerts';

        return view($view, compact('course', 'alerts'));
    }

    public function evaluate(LmsCourse $course)
    {
        $this->ensureEdit($course);
        $n = $this->alerts->evaluateCourse($course);

        return $this->backAlert($course, "Đã quét cảnh báo — {$n} cảnh báo mới.");
    }

    public function markRead(LmsCourse $course, LmsLearningAlert $alert)
    {
        $this->ensureVisible($course);
        if ((int) $alert->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        if ((int) $alert->user_id !== (int) Auth::id() && ! Auth::user()?->can('lms.edit')) {
            abort(403);
        }
        $alert->update(['is_read' => true]);

        return back()->with('success', 'Đã đánh dấu đã đọc.');
    }

    public function resolve(Request $request, LmsCourse $course, LmsLearningAlert $alert)
    {
        $this->ensureEdit($course);
        if ((int) $alert->lms_course_id !== (int) $course->id) {
            abort(404);
        }

        $note = trim((string) $request->input('note', ''));
        $meta = is_array($alert->meta) ? $alert->meta : [];
        if ($note !== '') {
            $meta['resolve_note'] = $note;
            $meta['resolved_by'] = Auth::id();
        }

        $alert->update([
            'resolved_at' => now(),
            'is_read' => true,
            'meta' => $meta,
        ]);

        return $this->backAlert($course, 'Đã xử lý cảnh báo.');
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureEdit(LmsCourse $course): void
    {
        $user = Auth::user();
        if (LmsAccess::usesAdminShell($user)) {
            $this->ensureVisible($course);

            return;
        }
        if ($user?->can('lms.edit') && LmsAccess::canTeachCourse($course, $user)) {
            return;
        }
        abort(403);
    }

    protected function backAlert(LmsCourse $course, string $message, bool $error = false)
    {
        if (request()->boolean('teach')
            || request()->input('return') === 'teach'
            || str_contains((string) url()->previous(), 'mode=teach')) {
            return redirect()
                ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=class')
                ->with($error ? 'error' : 'success', $message);
        }

        return back()->with($error ? 'error' : 'success', $message);
    }
}
