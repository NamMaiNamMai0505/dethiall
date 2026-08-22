<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsQuestion;
use Modules\Lms\Models\LmsQuestionBank;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsSettings;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sprint GV-4 — NHCH + đề thi + lượt làm trên portal GV.
 */
class ExamController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware(['auth', 'permission:lms.edit']);
    }

    public function storeBank(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
        ]);

        LmsQuestionBank::create([
            'lms_course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return $this->backExam($course, 'Đã tạo ngân hàng câu hỏi.');
    }

    public function storeQuestion(Request $request, LmsCourse $course, LmsQuestionBank $bank)
    {
        $this->ensureTeach($course);
        $this->ensureBank($course, $bank);

        $parsed = $this->parseQuestionPayload($request);
        if (isset($parsed['error'])) {
            return $this->backExam($course, $parsed['error'], true);
        }

        LmsQuestion::create([
            'lms_question_bank_id' => $bank->id,
            'type' => $parsed['type'],
            'stem' => $parsed['stem'],
            'options' => $parsed['options'],
            'correct_answer' => $parsed['correct_answer'],
            'points' => $parsed['points'],
            'sort_order' => ((int) $bank->questions()->max('sort_order')) + 1,
        ]);

        return $this->backExam($course, 'Đã thêm câu hỏi vào NHCH.');
    }

    /** G2: sửa câu hỏi NHCH */
    public function updateQuestion(Request $request, LmsCourse $course, LmsQuestionBank $bank, LmsQuestion $question)
    {
        $this->ensureTeach($course);
        $this->ensureBank($course, $bank);
        $this->ensureQuestion($bank, $question);

        $parsed = $this->parseQuestionPayload($request);
        if (isset($parsed['error'])) {
            return $this->backExam($course, $parsed['error'], true);
        }

        $question->update([
            'type' => $parsed['type'],
            'stem' => $parsed['stem'],
            'options' => $parsed['options'],
            'correct_answer' => $parsed['correct_answer'],
            'points' => $parsed['points'],
        ]);

        return $this->backExam($course, 'Đã cập nhật câu hỏi.');
    }

    /** G2: xóa câu hỏi */
    public function destroyQuestion(LmsCourse $course, LmsQuestionBank $bank, LmsQuestion $question)
    {
        $this->ensureTeach($course);
        $this->ensureBank($course, $bank);
        $this->ensureQuestion($bank, $question);
        $question->delete();

        return $this->backExam($course, 'Đã xoá câu hỏi.');
    }

    /** G2: sắp xếp ↑↓ */
    public function moveQuestion(Request $request, LmsCourse $course, LmsQuestionBank $bank, LmsQuestion $question)
    {
        $this->ensureTeach($course);
        $this->ensureBank($course, $bank);
        $this->ensureQuestion($bank, $question);

        $direction = $request->input('direction', 'up');
        $siblings = $bank->questions()->orderBy('sort_order')->orderBy('id')->get();
        $idx = $siblings->search(fn ($q) => (int) $q->id === (int) $question->id);
        if ($idx === false) {
            return $this->backExam($course, 'Không tìm thấy câu hỏi.', true);
        }

        $swapWith = $direction === 'down' ? $idx + 1 : $idx - 1;
        if ($swapWith < 0 || $swapWith >= $siblings->count()) {
            return $this->backExam($course, 'Không thể di chuyển thêm.');
        }

        $a = $siblings[$idx];
        $b = $siblings[$swapWith];
        $orderA = $a->sort_order;
        $a->update(['sort_order' => $b->sort_order]);
        $b->update(['sort_order' => $orderA]);

        return $this->backExam($course, 'Đã sắp xếp lại câu hỏi.');
    }

    public function storeExam(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'pass_score' => 'nullable|numeric|min:0',
            'bank_id' => 'nullable|integer|exists:lms_question_banks,id',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:lms_questions,id',
            'is_published' => 'nullable|boolean',
            'proctor_basic' => 'nullable|boolean',
            'require_fullscreen' => 'nullable|boolean',
            'auto_submit_on_leave' => 'nullable|boolean',
            'max_blur_events' => 'nullable|integer|min:1|max:50',
            'shuffle_questions' => 'nullable|boolean',
        ]);

        $bankId = $data['bank_id'] ?? null;
        if ($bankId) {
            $bank = LmsQuestionBank::query()->find($bankId);
            if (! $bank || (int) $bank->lms_course_id !== (int) $course->id) {
                return $this->backExam($course, 'NHCH không thuộc khóa này.', true);
            }
        }

        // G1: ưu tiên question_ids (câu lẻ); không có thì lấy cả bank_id
        $qids = array_values(array_unique(array_map('intval', $data['question_ids'] ?? [])));
        if ($qids) {
            $valid = LmsQuestion::query()
                ->whereIn('id', $qids)
                ->whereHas('bank', fn ($q) => $q->where('lms_course_id', $course->id))
                ->pluck('id')
                ->all();
            $qids = array_values(array_intersect($qids, $valid));
            if (! $qids) {
                return $this->backExam($course, 'Không có câu hỏi hợp lệ được chọn.', true);
            }
        } elseif ($bankId) {
            $qids = LmsQuestion::query()
                ->where('lms_question_bank_id', $bankId)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        }

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
            'require_fullscreen' => $request->boolean('require_fullscreen'),
            'auto_submit_on_leave' => $request->boolean('auto_submit_on_leave'),
            'max_blur_events' => $data['max_blur_events'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'created_by' => Auth::id(),
        ]);

        $attach = [];
        foreach (array_values($qids) as $i => $qid) {
            $attach[$qid] = ['sort_order' => $i + 1];
        }
        if ($attach) {
            $exam->questions()->attach($attach);
        }

        return $this->backExam($course, 'Đã tạo bài thi ('.count($attach).' câu).');
    }

    public function toggleExam(LmsCourse $course, LmsExam $exam)
    {
        $this->ensureTeach($course);
        $this->ensureExam($course, $exam);
        $exam->update(['is_published' => ! $exam->is_published]);

        return $this->backExam(
            $course,
            $exam->is_published ? 'Đã công bố bài thi.' : 'Đã ẩn bài thi.'
        );
    }

    public function destroyExam(LmsCourse $course, LmsExam $exam)
    {
        $this->ensureTeach($course);
        $this->ensureExam($course, $exam);
        $exam->delete();

        return $this->backExam($course, 'Đã xoá bài thi.');
    }

    public function attempts(LmsCourse $course, LmsExam $exam)
    {
        $this->ensureTeach($course);
        $this->ensureExam($course, $exam);

        $rows = $exam->attempts()->with('user:id,name,email')->orderByDesc('id')->get();

        return view('lms::teach.exam-attempts', compact('course', 'exam', 'rows'));
    }

    public function exportCsv(LmsCourse $course, LmsExam $exam): StreamedResponse
    {
        $this->ensureTeach($course);
        $this->ensureExam($course, $exam);

        $rows = $exam->attempts()->with('user:id,name,email')->orderBy('id')->get();
        $filename = 'thi-'.$exam->id.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM Excel
            fputcsv($out, ['user_id', 'name', 'email', 'started_at', 'submitted_at', 'score', 'max_score', 'blur_count', 'status']);
            foreach ($rows as $att) {
                fputcsv($out, [
                    $att->user_id,
                    $att->user->name ?? '',
                    $att->user->email ?? '',
                    optional($att->started_at)->format('Y-m-d H:i:s'),
                    optional($att->submitted_at)->format('Y-m-d H:i:s'),
                    $att->score,
                    $att->max_score,
                    $att->blur_count,
                    $att->status,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function ensureTeach(LmsCourse $course): void
    {
        if (! LmsAccess::canTeachCourse($course)) {
            abort(403, 'Bạn không phụ trách khóa học này.');
        }
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureBank(LmsCourse $course, LmsQuestionBank $bank): void
    {
        if ((int) $bank->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function ensureExam(LmsCourse $course, LmsExam $exam): void
    {
        if ((int) $exam->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function ensureQuestion(LmsQuestionBank $bank, LmsQuestion $question): void
    {
        if ((int) $question->lms_question_bank_id !== (int) $bank->id) {
            abort(404);
        }
    }

    /**
     * @return array{type:string,stem:string,options:?array,correct_answer:string,points:float}|array{error:string}
     */
    protected function parseQuestionPayload(Request $request): array
    {
        $data = $request->validate([
            'type' => 'required|in:mcq,true_false,short',
            'stem' => 'required|string|max:5000',
            'options' => 'nullable|string|max:5000',
            'correct_answer' => 'required|string|max:500',
            'points' => 'nullable|numeric|min:0|max:100',
        ]);

        $options = null;
        if ($data['type'] === 'mcq') {
            $raw = trim((string) ($data['options'] ?? ''));
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $raw) ?: [])));
            if (count($options) < 2) {
                return ['error' => 'MCQ cần ≥ 2 phương án (mỗi dòng 1 đáp án).'];
            }
        } elseif ($data['type'] === 'true_false') {
            $options = ['true', 'false'];
        }

        return [
            'type' => $data['type'],
            'stem' => $data['stem'],
            'options' => $options,
            'correct_answer' => $data['correct_answer'],
            'points' => (float) ($data['points'] ?? 1),
        ];
    }

    protected function backExam(LmsCourse $course, string $message, bool $error = false)
    {
        return redirect()
            ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=exam')
            ->with($error ? 'error' : 'success', $message);
    }
}
