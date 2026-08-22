<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsQuestion;
use Modules\Lms\Models\LmsQuestionBank;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsExamService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsSettings;

class ExamController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsExamService $exams,
    ) {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.exams', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $exams = LmsExam::query()
            ->where('lms_course_id', $course->id)
            ->when(LmsAccess::usesLearnerShell(), fn ($q) => $q->where('is_published', true))
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        $banks = LmsQuestionBank::query()
            ->where('lms_course_id', $course->id)
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        $view = LmsAccess::usesAdminShell() ? 'lms::exams.index' : 'lms::learn.exams';

        return view($view, compact('course', 'exams', 'banks'));
    }

    public function storeBank(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate(['title' => 'required|string|max:200', 'description' => 'nullable|string']);
        LmsQuestionBank::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Đã tạo ngân hàng đề.');
    }

    public function storeQuestion(Request $request, LmsCourse $course, LmsQuestionBank $bank)
    {
        $this->ensureEdit($course);
        if ((int) $bank->lms_course_id !== (int) $course->id) {
            abort(404);
        }

        $data = $request->validate([
            'type' => 'required|in:mcq,true_false,short',
            'stem' => 'required|string',
            'options' => 'nullable|string', // lines or JSON
            'correct_answer' => 'required|string|max:500',
            'points' => 'nullable|numeric|min:0',
        ]);

        $options = null;
        if ($data['type'] === 'mcq') {
            $raw = trim((string) ($data['options'] ?? ''));
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $raw) ?: [])));
            if (count($options) < 2) {
                return back()->with('error', 'MCQ cần ít nhất 2 phương án (mỗi dòng 1 đáp án).')->withInput();
            }
        } elseif ($data['type'] === 'true_false') {
            $options = ['true', 'false'];
        }

        LmsQuestion::create([
            'lms_question_bank_id' => $bank->id,
            'type' => $data['type'],
            'stem' => $data['stem'],
            'options' => $options,
            'correct_answer' => $data['correct_answer'],
            'points' => $data['points'] ?? 1,
            'sort_order' => ((int) $bank->questions()->max('sort_order')) + 1,
        ]);

        return back()->with('success', 'Đã thêm câu hỏi.');
    }

    public function storeExam(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after_or_equal:opens_at',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'pass_score' => 'nullable|numeric|min:0',
            'bank_id' => 'nullable|exists:lms_question_banks,id',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:lms_questions,id',
            'is_published' => 'nullable|boolean',
            'proctor_basic' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
        ]);

        $exam = LmsExam::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? LmsSettings::examDurationMinutes(),
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? LmsSettings::examAttempts(),
            'pass_score' => $data['pass_score'] ?? LmsSettings::examPassScore(),
            'shuffle_questions' => $request->boolean('shuffle_questions', LmsSettings::shuffleQuestions()),
            'shuffle_options' => true,
            'proctor_basic' => $request->boolean('proctor_basic', true),
            'is_published' => $request->boolean('is_published'),
            'created_by' => Auth::id(),
        ]);

        $qids = $data['question_ids'] ?? [];
        if (empty($qids) && ! empty($data['bank_id'])) {
            $qids = LmsQuestion::query()->where('lms_question_bank_id', $data['bank_id'])->pluck('id')->all();
        }
        $attach = [];
        foreach (array_values($qids) as $i => $qid) {
            $attach[$qid] = ['sort_order' => $i + 1];
        }
        if ($attach) {
            $exam->questions()->attach($attach);
        }

        return back()->with('success', 'Đã tạo bài thi với '.count($attach).' câu hỏi.');
    }

    public function take(LmsCourse $course, LmsExam $exam)
    {
        $this->ensureVisible($course);
        $this->ensureExam($course, $exam);

        try {
            $attempt = $this->exams->startAttempt($exam);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $order = $attempt->question_order ?? [];
        $questions = LmsQuestion::query()->whereIn('id', $order)->get()->keyBy('id');
        $ordered = collect($order)->map(fn ($id) => $questions->get($id))->filter()->values();

        // Shuffle options display (not mutating correct keys)
        $ordered = $ordered->map(function (LmsQuestion $q) use ($exam) {
            if ($exam->shuffle_options && is_array($q->options) && $q->type === 'mcq') {
                $opts = $q->options;
                $keys = array_keys($opts);
                shuffle($keys);
                $q->setAttribute('display_options', array_map(fn ($k) => ['key' => (string) $k, 'text' => $opts[$k]], $keys));
            } elseif (is_array($q->options)) {
                $q->setAttribute('display_options', collect($q->options)->map(fn ($t, $k) => ['key' => (string) $k, 'text' => $t])->values()->all());
            } else {
                $q->setAttribute('display_options', []);
            }

            return $q;
        });

        $endsAt = $attempt->started_at?->copy()->addMinutes((int) $exam->duration_minutes);

        return view('lms::learn.exam-take', compact('course', 'exam', 'attempt', 'ordered', 'endsAt'));
    }

    public function submit(Request $request, LmsCourse $course, LmsExam $exam, LmsExamAttempt $attempt)
    {
        $this->ensureVisible($course);
        $this->ensureExam($course, $exam);
        if ((int) $attempt->lms_exam_id !== (int) $exam->id || (int) $attempt->user_id !== (int) Auth::id()) {
            abort(403);
        }
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('lms.learn.exams.result', [$course, $exam, $attempt]);
        }

        $answers = $request->input('answers', []);
        $proctor = $request->input('proctor_events', []);
        if (is_string($proctor)) {
            $proctor = json_decode($proctor, true) ?: [];
        }

        $attempt = $this->exams->submitAttempt($attempt, is_array($answers) ? $answers : [], is_array($proctor) ? $proctor : []);

        return redirect()
            ->route('lms.learn.exams.result', [$course, $exam, $attempt])
            ->with('success', 'Đã nộp bài thi. Điểm: '.$attempt->score.'/'.$attempt->max_score);
    }

    /** Sprint 9 T4 — heartbeat proctor events (JSON) */
    public function proctorHeartbeat(Request $request, LmsCourse $course, LmsExam $exam, LmsExamAttempt $attempt)
    {
        $this->ensureVisible($course);
        $this->ensureExam($course, $exam);
        if ((int) $attempt->lms_exam_id !== (int) $exam->id || (int) $attempt->user_id !== (int) Auth::id()) {
            abort(403);
        }
        $events = $request->input('proctor_events', []);
        if (is_string($events)) {
            $events = json_decode($events, true) ?: [];
        }
        if (! is_array($events)) {
            $events = [];
        }
        $attempt = $this->exams->appendProctorEvents($attempt, $events);

        return response()->json([
            'ok' => true,
            'blur_count' => $attempt->blur_count,
            'events' => count($attempt->proctor_events ?? []),
        ]);
    }

    public function result(LmsCourse $course, LmsExam $exam, LmsExamAttempt $attempt)
    {
        $this->ensureVisible($course);
        if ((int) $attempt->user_id !== (int) Auth::id() && ! Auth::user()?->can('lms.edit')) {
            abort(403);
        }

        $canSeeScore = (bool) $exam->publish_score_after_submit || Auth::user()?->can('lms.edit');
        $details = collect();
        if ($attempt->status === 'graded' && $canSeeScore) {
            $order = $attempt->question_order ?? [];
            $answers = $attempt->answers ?? [];
            $questions = LmsQuestion::query()->whereIn('id', $order)->get()->keyBy('id');
            $pointsByQuestion = $exam->questions()->pluck('lms_exam_questions.points', 'lms_questions.id');

            $details = collect($order)
                ->map(fn ($id) => $questions->get($id))
                ->filter()
                ->values()
                ->map(function (LmsQuestion $q) use ($answers, $pointsByQuestion) {
                    $given = $answers[(string) $q->id] ?? $answers[$q->id] ?? null;
                    $optionsMap = is_array($q->options) ? $q->options : [];

                    return [
                        'question' => $q,
                        'points' => (float) ($pointsByQuestion[$q->id] ?? $q->points ?? 1),
                        'given' => $given,
                        'given_label' => $given !== null && $given !== ''
                            ? ($optionsMap[$given] ?? $given)
                            : null,
                        'correct_label' => $optionsMap[$q->correct_answer] ?? $q->correct_answer,
                        'is_correct' => $q->isCorrect($given !== null ? (string) $given : null),
                    ];
                });
        }

        return view('lms::learn.exam-result', compact('course', 'exam', 'attempt', 'details', 'canSeeScore'));
    }

    public function attempts(LmsCourse $course, LmsExam $exam)
    {
        $this->ensureEdit($course);
        $this->ensureExam($course, $exam);
        $rows = $exam->attempts()->with('user')->orderByDesc('id')->get();

        return view('lms::exams.attempts', compact('course', 'exam', 'rows'));
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureEdit(LmsCourse $course): void
    {
        if (! Auth::user()?->can('lms.edit')) {
            abort(403);
        }
        $this->ensureVisible($course);
    }

    protected function ensureExam(LmsCourse $course, LmsExam $exam): void
    {
        if ((int) $exam->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }
}
