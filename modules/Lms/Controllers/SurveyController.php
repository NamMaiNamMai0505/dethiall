<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsSurvey;
use Modules\Lms\Models\LmsSurveyQuestion;
use Modules\Lms\Models\LmsSurveyResponse;
use Modules\Lms\Models\LmsSurveyTemplate;
use Modules\Lms\Models\LmsSurveyTemplateQuestion;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;

class SurveyController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.surveys', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $surveys = LmsSurvey::query()
            ->where('lms_course_id', $course->id)
            ->withCount(['questions', 'responses'])
            ->orderByDesc('id')
            ->get();

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::surveys.index'
            : 'lms::learn.surveys';

        return view($view, compact('course', 'surveys'));
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $survey = LmsSurvey::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'is_anonymous' => $request->boolean('is_anonymous'),
            'created_by' => Auth::id(),
        ]);

        // default quality questions — đánh giá khóa + GV bằng sao
        $defaults = [
            ['type' => 'rating_1_5', 'stem' => 'Đánh giá tổng thể khóa học (số sao)'],
            ['type' => 'rating_1_5', 'stem' => 'Nội dung / học liệu của khóa học'],
            ['type' => 'rating_1_5', 'stem' => 'Đánh giá giảng viên phụ trách'],
            ['type' => 'rating_1_5', 'stem' => 'Phương pháp giảng dạy của giảng viên'],
            ['type' => 'rating_1_5', 'stem' => 'Tiện ích nền tảng LMS'],
            ['type' => 'text', 'stem' => 'Góp ý thêm về khóa học / giảng viên (tuỳ chọn)', 'is_required' => false],
        ];
        foreach ($defaults as $i => $q) {
            LmsSurveyQuestion::create([
                'lms_survey_id' => $survey->id,
                'type' => $q['type'],
                'stem' => $q['stem'],
                'is_required' => $q['is_required'] ?? true,
                'sort_order' => $i + 1,
            ]);
        }

        return $this->backSurvey($course, 'Đã tạo khảo sát kèm câu hỏi mặc định.');
    }

    public function storeQuestion(Request $request, LmsCourse $course, LmsSurvey $survey)
    {
        $this->ensureEdit($course);
        $this->ensureSurvey($course, $survey);
        $data = $request->validate([
            'type' => 'required|in:rating_1_5,mcq,text',
            'stem' => 'required|string',
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
        ]);
        $options = null;
        if ($data['type'] === 'mcq') {
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', (string) ($data['options'] ?? '')) ?: [])));
        }
        LmsSurveyQuestion::create([
            'lms_survey_id' => $survey->id,
            'type' => $data['type'],
            'stem' => $data['stem'],
            'options' => $options,
            'is_required' => $request->boolean('is_required', true),
            'sort_order' => ((int) $survey->questions()->max('sort_order')) + 1,
        ]);

        return $this->backSurvey($course, 'Đã thêm câu hỏi.');
    }

    public function show(LmsCourse $course, LmsSurvey $survey)
    {
        $this->ensureVisible($course);
        $this->ensureSurvey($course, $survey);
        $survey->load('questions');
        $my = LmsSurveyResponse::query()
            ->where('lms_survey_id', $survey->id)
            ->where('user_id', Auth::id())
            ->first();

        $isAdmin = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit');
        if ($isAdmin) {
            $responses = $survey->responses()->with('user')->latest('submitted_at')->get();
            $stats = $this->buildStats($survey, $responses);

            return view('lms::surveys.show', compact('course', 'survey', 'responses', 'stats', 'my'));
        }

        return view('lms::learn.survey-take', compact('course', 'survey', 'my'));
    }

    public function submit(Request $request, LmsCourse $course, LmsSurvey $survey)
    {
        $this->ensureVisible($course);
        $this->ensureSurvey($course, $survey);
        if (! $survey->isOpen()) {
            return back()->with('error', 'Khảo sát chưa mở hoặc đã đóng.');
        }

        $survey->load('questions');
        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            $answers = [];
        }

        // Chuẩn hoá key string → giữ nguyên map id => value
        $normalized = [];
        foreach ($answers as $qid => $val) {
            $normalized[(string) $qid] = is_scalar($val) ? (string) $val : $val;
        }

        foreach ($survey->questions as $q) {
            $key = (string) $q->id;
            $val = $normalized[$key] ?? $normalized[$q->id] ?? null;
            if ($q->is_required && ($val === null || $val === '')) {
                return redirect()
                    ->to(route('lms.learn.courses.show', $course).'?tab=surveys')
                    ->with('error', 'Vui lòng trả lời: '.$q->stem)
                    ->withInput();
            }
        }

        LmsSurveyResponse::query()->updateOrCreate(
            [
                'lms_survey_id' => $survey->id,
                'user_id' => Auth::id(),
            ],
            [
                'answers' => $normalized,
                'submitted_at' => now(),
            ]
        );

        if (! LmsAccess::usesAdminShell()) {
            return redirect()
                ->to(route('lms.learn.courses.show', $course).'?tab=surveys')
                ->with('success', 'Cảm ơn bạn đã gửi khảo sát.');
        }

        return back()->with('success', 'Cảm ơn bạn đã gửi khảo sát.');
    }

    public function publish(LmsCourse $course, LmsSurvey $survey)
    {
        $this->ensureEdit($course);
        $this->ensureSurvey($course, $survey);
        $survey->update(['is_published' => ! $survey->is_published]);

        return $this->backSurvey(
            $course,
            $survey->is_published ? 'Đã công bố khảo sát.' : 'Đã ẩn khảo sát.'
        );
    }

    /**
     * Sprint 9 M6 — thư viện template khảo sát (admin).
     */
    public function templatesIndex()
    {
        if (! Auth::user()?->can('lms.create') && ! Auth::user()?->can('lms.manage')) {
            abort(403);
        }
        $templates = LmsSurveyTemplate::query()
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        return view('lms::surveys.templates-index', compact('templates'));
    }

    public function templatesStore(Request $request)
    {
        if (! Auth::user()?->can('lms.create') && ! Auth::user()?->can('lms.manage')) {
            abort(403);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);
        $tpl = LmsSurveyTemplate::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);
        // seed default questions
        $defaults = [
            ['type' => 'rating_1_5', 'stem' => 'Đánh giá tổng thể khóa học'],
            ['type' => 'rating_1_5', 'stem' => 'Nội dung / học liệu'],
            ['type' => 'rating_1_5', 'stem' => 'Giảng viên phụ trách'],
            ['type' => 'text', 'stem' => 'Góp ý thêm (tuỳ chọn)', 'is_required' => false],
        ];
        foreach ($defaults as $i => $q) {
            LmsSurveyTemplateQuestion::create([
                'lms_survey_template_id' => $tpl->id,
                'type' => $q['type'],
                'stem' => $q['stem'],
                'is_required' => $q['is_required'] ?? true,
                'sort_order' => $i + 1,
            ]);
        }

        return redirect()->route('lms.survey-templates.show', $tpl)
            ->with('success', 'Đã tạo template khảo sát.');
    }

    public function templatesShow(LmsSurveyTemplate $template)
    {
        if (! Auth::user()?->can('lms.create') && ! Auth::user()?->can('lms.manage') && ! Auth::user()?->can('lms.edit')) {
            abort(403);
        }
        $template->load('questions');

        return view('lms::surveys.templates-show', compact('template'));
    }

    public function templatesStoreQuestion(Request $request, LmsSurveyTemplate $template)
    {
        if (! Auth::user()?->can('lms.create') && ! Auth::user()?->can('lms.manage')) {
            abort(403);
        }
        $data = $request->validate([
            'type' => 'required|in:rating_1_5,mcq,text',
            'stem' => 'required|string',
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
        ]);
        $options = null;
        if ($data['type'] === 'mcq') {
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', (string) ($data['options'] ?? '')) ?: [])));
        }
        LmsSurveyTemplateQuestion::create([
            'lms_survey_template_id' => $template->id,
            'type' => $data['type'],
            'stem' => $data['stem'],
            'options' => $options,
            'is_required' => $request->boolean('is_required', true),
            'sort_order' => ((int) $template->questions()->max('sort_order')) + 1,
        ]);

        return back()->with('success', 'Đã thêm câu vào template.');
    }

    /** Clone template → survey của khóa */
    public function applyTemplate(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'template_id' => 'required|integer|exists:lms_survey_templates,id',
            'title' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
        ]);
        $tpl = LmsSurveyTemplate::query()->with('questions')->findOrFail($data['template_id']);
        if (! $tpl->is_active) {
            return $this->backSurvey($course, 'Template đang tắt.', true);
        }

        $survey = LmsSurvey::create([
            'lms_course_id' => $course->id,
            'lms_survey_template_id' => $tpl->id,
            'title' => $data['title'] ?: $tpl->title,
            'description' => $tpl->description,
            'is_published' => $request->boolean('is_published'),
            'is_anonymous' => false,
            'created_by' => Auth::id(),
        ]);

        foreach ($tpl->questions as $i => $q) {
            LmsSurveyQuestion::create([
                'lms_survey_id' => $survey->id,
                'type' => $q->type,
                'stem' => $q->stem,
                'options' => $q->options,
                'is_required' => $q->is_required,
                'sort_order' => $q->sort_order ?: ($i + 1),
            ]);
        }

        return $this->backSurvey($course, 'Đã áp template «'.$tpl->title.'» ('.$tpl->questions->count().' câu).');
    }

    protected function buildStats(LmsSurvey $survey, $responses): array
    {
        $stats = [];
        foreach ($survey->questions as $q) {
            if ($q->type === 'rating_1_5') {
                $vals = [];
                foreach ($responses as $r) {
                    $v = $r->answers[(string) $q->id] ?? $r->answers[$q->id] ?? null;
                    if (is_numeric($v)) {
                        $vals[] = (float) $v;
                    }
                }
                $stats[$q->id] = [
                    'avg' => $vals ? round(array_sum($vals) / count($vals), 2) : null,
                    'count' => count($vals),
                ];
            } else {
                $stats[$q->id] = ['count' => $responses->count()];
            }
        }

        return $stats;
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

    protected function ensureSurvey(LmsCourse $course, LmsSurvey $survey): void
    {
        if ((int) $survey->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function backSurvey(LmsCourse $course, string $message, bool $error = false)
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
