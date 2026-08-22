<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCertificate;
use Modules\Lms\Models\LmsCertificateTemplate;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCertificateService;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class CertificateController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsCertificateService $certs,
    ) {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.certificates', ApplicationRegistry::ACTION_VIEW))->except(['verify']);
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $template = LmsCertificateTemplate::query()->where('lms_course_id', $course->id)->latest('id')->first();
        $certificates = LmsCertificate::query()
            ->where('lms_course_id', $course->id)
            ->with('user')
            ->orderByDesc('issued_at')
            ->get();

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::certificates.index'
            : 'lms::learn.certificates';

        $mine = LmsCertificate::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', Auth::id())
            ->first();
        $eligibility = $this->certs->evaluateEligibility($course, Auth::user());

        return view($view, compact('course', 'template', 'certificates', 'mine', 'eligibility'));
    }

    public function saveTemplate(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'issuer_name' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
            'layout_json' => 'nullable|string', // JSON string from designer
            'min_score' => 'nullable|numeric|min:0|max:10',
            'min_progress_pct' => 'nullable|numeric|min:0|max:100',
            'require_survey' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $layout = null;
        if (! empty($data['layout_json'])) {
            $decoded = json_decode($data['layout_json'], true);
            $layout = is_array($decoded) ? $decoded : null;
        }

        LmsCertificateTemplate::query()->updateOrCreate(
            ['lms_course_id' => $course->id],
            [
                'title' => $data['title'],
                'issuer_name' => $data['issuer_name'] ?? 'Trường Cao đẳng Hậu cần 2',
                'body_html' => $data['body_html'] ?? null,
                'layout_json' => $layout,
                'min_score' => $data['min_score'] ?? null,
                'min_progress_pct' => $data['min_progress_pct'] ?? 80,
                'require_survey' => $request->boolean('require_survey'),
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
            ]
        );

        return $this->backCert($course, 'Đã lưu mẫu chứng chỉ (kèm layout designer).');
    }

    public function issueOne(LmsCourse $course, User $user)
    {
        $this->ensureEdit($course);
        // Admin: force; GV teach: chỉ khi đủ ĐK (policy Sprint 7 — không force)
        $force = LmsAccess::usesAdminShell() && ! request()->boolean('teach');
        try {
            $cert = $this->certs->issue($course, $user, null, $force);
        } catch (\Throwable $e) {
            return $this->backCert($course, $e->getMessage(), true);
        }

        return $this->backCert($course, 'Đã cấp chứng chỉ '.$cert->code.' cho '.$user->name);
    }

    public function issueEligible(LmsCourse $course)
    {
        $this->ensureEdit($course);
        $n = $this->certs->issueEligible($course);

        return $this->backCert($course, "Đã cấp {$n} chứng chỉ cho HV đủ điều kiện.");
    }

    public function requestIssue(LmsCourse $course)
    {
        $this->ensureVisible($course);
        try {
            $cert = $this->certs->issue($course, Auth::user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.learn.certificates.show', [$course, $cert])
            ->with('success', 'Đã cấp chứng chỉ: '.$cert->code);
    }

    public function show(LmsCourse $course, LmsCertificate $certificate)
    {
        $this->ensureVisible($course);
        if ((int) $certificate->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        if ((int) $certificate->user_id !== (int) Auth::id() && ! Auth::user()?->can('lms.edit') && ! LmsAccess::usesAdminShell()) {
            abort(403);
        }
        $certificate->load(['user', 'course', 'template']);

        return view('lms::certificates.show', compact('course', 'certificate'));
    }

    /** Public verify by code */
    public function verify(Request $request)
    {
        $code = trim((string) $request->query('code', ''));
        $certificate = $code
            ? LmsCertificate::query()->with(['user', 'course'])->where('code', $code)->where('status', 'issued')->first()
            : null;

        return view('lms::certificates.verify', compact('code', 'certificate'));
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
        if (! $user?->can('lms.edit')) {
            abort(403);
        }
        $this->ensureVisible($course);
    }

    protected function backCert(LmsCourse $course, string $message, bool $error = false)
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
