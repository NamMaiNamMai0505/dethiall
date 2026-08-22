<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SystemNotifier;
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
use Modules\EssayExam\Controllers\EssayExamController;
use Modules\Lms\Models\LmsLesson;

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

    public function importBank(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);
        $data = $request->validate([
            'file' => 'required|file|extensions:txt,csv,tsv,doc,docx|max:10240',
            'lms_lesson_id' => 'required|integer|exists:lms_lessons,id',
        ]);
        $lessonIds = [(int) $data['lms_lesson_id']];
        abort_unless($course->lessons()->whereIn('id', $lessonIds)->count() === count($lessonIds), 422, 'Có bài học không thuộc khóa học này.');
        $parser = app(EssayExamController::class);
        $rows = $parser->parseImportRows($parser->readImportText($request->file('file')));
        $rows = array_values(array_filter($rows, fn ($row) => ($row['question_type'] ?? '') === 'multiple_choice' && count($row['options'] ?? []) >= 2));
        abort_if(! $rows, 422, 'Không nhận diện được câu trắc nghiệm trong file.');
        $lessons = $course->lessons()->whereIn('id', $lessonIds)->get()->keyBy('id');
        $paperNumbers = collect($rows)->pluck('paper')->map(fn ($paper) => (int) $paper ?: 1)->unique()->sort()->values();
        abort_if($paperNumbers->count() > count($lessonIds), 422, 'File có nhiều bài hơn số bài học được chọn.');
        $lessonTitle = $lessons->get($lessonIds[0])?->title ?: 'Bài học';
        $bank = LmsQuestionBank::create([
            'lms_course_id' => $course->id,
            'title' => 'Ngân hàng trắc nghiệm - '.($course->subject?->name ?: $course->title).' - '.($paperNumbers->count() > 1 ? $paperNumbers->count().' bài' : $lessonTitle),
            'description' => 'Import theo nhiều bài học; chỉ duyệt các câu trong lần import này.',
            'created_by' => Auth::id(),
            'status' => 'DRAFT',
        ]);
        $order = 0;
        foreach ($rows as $row) {
            $paper = max(1, (int) ($row['paper'] ?? 1));
            $lessonId = $lessonIds[$paper - 1] ?? $lessonIds[0];
            $answer = strtoupper(trim((string) ($row['answer'] ?? '')));
            $answer = preg_match('/^[A-D]$/', $answer) ? ord($answer) - ord('A') : ($answer === '' ? 0 : $answer);
            $bank->questions()->create(['lms_lesson_id'=>$lessonId,'type'=>'mcq','stem'=>$row['content'],'options'=>array_values($row['options']),'correct_answer'=>(string)$answer,'points'=>$row['points'] ?? 1,'sort_order'=>++$order]);
        }
        return $this->backExam($course, 'Đã import '.count($rows).' câu cho '.$paperNumbers->count().' bài học. Hãy kiểm tra và gửi duyệt một lần.');
    }

    public function submitBank(Request $request, LmsCourse $course, LmsQuestionBank $bank)
    {
        $this->ensureTeach($course);
        abort_unless((int)$bank->lms_course_id === (int)$course->id, 404);
        abort_unless($bank->questions()->exists(), 422, 'Ngân hàng chưa có câu hỏi.');
        $bank->update(['status'=>'PENDING_DEPT','submitted_at'=>now()]);
        $this->notifyQuestionBankApprovers($course, $request->user(), 1, $bank->title, 'dept');
        return $this->backExam($course, 'Đã gửi ngân hàng câu hỏi chờ chủ nhiệm khoa duyệt.');
    }

    public function submitPendingBanks(LmsCourse $course)
    {
        $this->ensureTeach($course);
        $banks = LmsQuestionBank::query()
            ->where('lms_course_id', $course->id)
            ->whereIn('status', ['DRAFT', 'RETURNED'])
            ->whereHas('questions')
            ->get();

        abort_if($banks->isEmpty(), 422, 'Không có ngân hàng bản nháp nào để gửi duyệt.');
        LmsQuestionBank::whereKey($banks->pluck('id'))->update([
            'status' => 'PENDING_DEPT',
            'submitted_at' => now(),
        ]);

        $this->notifyQuestionBankApprovers($course, request()->user(), $banks->count(), null, 'dept');
        return $this->backExam($course, 'Đã gửi '.$banks->count().' ngân hàng và toàn bộ câu hỏi chờ chủ nhiệm khoa duyệt.');
    }

    public function approveBank(Request $request, LmsCourse $course, LmsQuestionBank $bank)
    {
        abort_unless((int)$bank->lms_course_id === (int)$course->id, 404);
        $user = $request->user();
        if ($bank->status === 'PENDING_DEPT') {
            abort_unless($user->hasAnyRole(['department-head','head-of-department','faculty-manager','super-admin']), 403);
            $bank->update(['status'=>'PENDING_EXAM_OFFICE']);
            $this->notifyQuestionBankApprovers($course, $user, 1, $bank->title, 'exam');
            return $this->backExam($course, 'Đã duyệt qua cấp khoa, chuyển khảo thí.');
        }
        if ($bank->status === 'PENDING_EXAM_OFFICE') {
            abort_unless($user->hasAnyRole(['exam-manager','exam-office','testing-office','training-office-manager','super-admin']), 403);
            $bank->update(['status'=>'PENDING_BGH']);
            $this->notifyQuestionBankApprovers($course, $user, 1, $bank->title, 'bgh');
            return $this->backExam($course, 'Đã duyệt qua khảo thí, chuyển Ban Giám hiệu.');
        }
        if ($bank->status === 'PENDING_BGH') {
            abort_unless($user->hasAnyRole(['bgh','board-of-management','ban giám hiệu','super-admin']), 403);
            $bank->update(['status'=>'APPROVED','approved_at'=>now(),'approved_by'=>$user->id]);
            return $this->backExam($course, 'Đã được Ban Giám hiệu duyệt ngân hàng câu hỏi trắc nghiệm.');
        }
        abort(422, 'Ngân hàng không ở trạng thái chờ duyệt.');
    }

    public function destroyBank(LmsCourse $course, LmsQuestionBank $bank)
    {
        $this->ensureTeach($course); abort_unless((int)$bank->lms_course_id === (int)$course->id, 404);
        abort_unless(in_array($bank->status, [null, 'DRAFT', 'RETURNED'], true), 422, 'Chỉ được xóa ngân hàng ở trạng thái bản nháp hoặc trả lại.');
        $bank->delete();
        return $this->backExam($course, 'Đã xóa ngân hàng câu hỏi.');
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
            'random_count' => 'nullable|integer|min:1|max:200',
            'lesson_counts' => 'nullable|array',
            'lesson_counts.*' => 'nullable|integer|min:0|max:200',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:lms_questions,id',
            'is_published' => 'nullable|boolean',
            'publish_score_after_submit' => 'nullable|boolean',
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
            if ($bank->status !== 'APPROVED') {
                return $this->backExam($course, 'Ngân hàng câu hỏi chưa được khảo thí duyệt.', true);
            }
        }

        $approvedBankIds = LmsQuestionBank::query()
            ->where('lms_course_id', $course->id)
            ->where('status', 'APPROVED')
            ->pluck('id');
        $sourceBankIds = $bankId ? collect([$bankId]) : $approvedBankIds;

        // G1: ưu tiên question_ids (câu lẻ); không có thì lấy cả bank_id
        $lessonCounts = collect($data['lesson_counts'] ?? [])
            ->mapWithKeys(fn ($count, $lessonId) => [(int) $lessonId => (int) $count])
            ->filter(fn ($count) => $count > 0);
        if (array_key_exists('lesson_counts', $data) && $lessonCounts->isEmpty()) {
            return $this->backExam($course, 'HÃ£y nháº­p sá»‘ cÃ¢u Ä‘á» nghá»‹ cho Ã­t nháº¥t má»™t bÃ i.', true);
        }
        $qids = [];
        if ($lessonCounts->isNotEmpty()) {
            foreach ($lessonCounts as $lessonId => $requested) {
                $available = LmsQuestion::query()
                    ->whereIn('lms_question_bank_id', $sourceBankIds)
                    ->where('lms_lesson_id', $lessonId)
                    ->count();
                if ($requested > $available) {
                    return $this->backExam($course, "BÃ i {$lessonId} khÃ´ng Ä‘á»§ cÃ¢u há»i Ä‘á»ƒ rÃºt {$requested} cÃ¢u.", true);
                }
                $qids = array_merge($qids, LmsQuestion::query()
                    ->whereIn('lms_question_bank_id', $sourceBankIds)
                    ->where('lms_lesson_id', $lessonId)
                    ->inRandomOrder()
                    ->limit($requested)
                    ->pluck('id')->all());
            }
        } else {
            $qids = array_values(array_unique(array_map('intval', $data['question_ids'] ?? [])));
        }
        if ($qids) {
            $valid = LmsQuestion::query()
                ->whereIn('id', $qids)
                ->whereIn('lms_question_bank_id', $sourceBankIds)
                ->pluck('id')
                ->all();
            $qids = array_values(array_intersect($qids, $valid));
            if (! $qids) {
                return $this->backExam($course, 'Không có câu hỏi hợp lệ được chọn.', true);
            }
        } elseif ($bankId) {
            $qids = LmsQuestion::query()
                ->where('lms_question_bank_id', $bankId)
                ->when(! empty($data['random_count']), fn ($q) => $q->inRandomOrder()->limit((int) $data['random_count']))
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        } elseif (! empty($data['random_count'])) {
            $qids = LmsQuestion::query()->whereIn('lms_question_bank_id', $sourceBankIds)
                ->inRandomOrder()->limit((int) $data['random_count'])->pluck('id')->all();
        }
        if (! empty($data['random_count']) && count($qids) < (int) $data['random_count']) {
            return $this->backExam($course, 'Ngân hàng không đủ câu hỏi để rút theo số lượng yêu cầu.', true);
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
            'publish_score_after_submit' => $request->boolean('publish_score_after_submit'),
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

    public function updateExamSchedule(Request $request, LmsCourse $course, LmsExam $exam)
    {
        $this->ensureTeach($course);
        abort_unless((int) $exam->lms_course_id === (int) $course->id, 404);
        $data = $request->validate([
            'duration_minutes' => 'required|integer|min:5|max:480',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after_or_equal:opens_at',
        ]);
        $exam->update($data);

        return $this->backExam($course, 'Đã cập nhật thời gian bài thi.');
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
            $answer = strtoupper(trim($data['correct_answer']));
            if (preg_match('/^[A-D]$/', $answer)) $data['correct_answer'] = (string) (ord($answer) - ord('A'));
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

    protected function notifyQuestionBankApprovers(LmsCourse $course, User $actor, int $count, ?string $title = null, string $stage = 'dept'): void
    {
        $roles = match ($stage) {
            'dept' => ['department-head', 'head-of-department', 'faculty-manager', 'super-admin'],
            'exam' => ['exam-manager', 'exam-office', 'testing-office', 'training-office-manager', 'super-admin'],
            default => ['bgh', 'board-of-management', 'ban giám hiệu', 'super-admin'],
        };
        $recipientIds = User::query()
            ->where('id', '!=', $actor->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->pluck('id');
        if ($recipientIds->isEmpty()) return;

        $stageLabel = match ($stage) {
            'dept' => 'cấp khoa',
            'exam' => 'khảo thí',
            default => 'Ban Giám hiệu',
        };
        $message = $title
            ? 'Ngân hàng "'.$title.'" có '.$count.' đợt/câu hỏi đang chờ '.$stageLabel.' duyệt.'
            : 'Có '.$count.' ngân hàng câu hỏi LMS đang chờ '.$stageLabel.' duyệt.';
        SystemNotifier::deliver(
            userIds: $recipientIds,
            actor: $actor,
            module: 'lms.question-banks',
            action: 'submit',
            title: 'Ngân hàng câu hỏi LMS chờ duyệt',
            message: $message,
            url: route('lms.learn.courses.show', $course, false).'?mode=teach&tab=exam',
            type: SystemNotifier::TYPE_SYSTEM_CHANGE,
            meta: ['lms_course_id' => $course->id, 'count' => $count, 'stage' => $stageLabel],
        );
    }
}
