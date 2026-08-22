<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAssignmentSubmissionVersion;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsSettings;

class AssignmentController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.assignments', ApplicationRegistry::ACTION_VIEW));
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $assignments = LmsAssignment::query()
            ->where('lms_course_id', $course->id)
            ->when(LmsAccess::usesLearnerShell(), fn ($q) => $q->where('is_published', true))
            ->with('lesson')
            ->orderByDesc('id')
            ->get();

        $mySubs = [];
        if (Auth::id()) {
            $mySubs = LmsAssignmentSubmission::query()
                ->whereIn('lms_assignment_id', $assignments->pluck('id'))
                ->where('user_id', Auth::id())
                ->get()
                ->keyBy('lms_assignment_id');
        }

        $lessons = $course->lessons()->orderBy('sort_order')->get(['id', 'title', 'sort_order']);
        $view = LmsAccess::usesAdminShell() ? 'lms::assignments.index' : 'lms::learn.assignments';

        return view($view, compact('course', 'assignments', 'mySubs', 'lessons'));
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'max_score' => 'nullable|numeric|min:0|max:1000',
            'allow_late' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'lms_lesson_id' => 'nullable|integer|exists:lms_lessons,id',
        ]);

        if (! empty($data['lms_lesson_id'])) {
            $belongs = $course->lessons()->where('id', $data['lms_lesson_id'])->exists();
            if (! $belongs) {
                return back()->with('error', 'Bài học không thuộc khóa này.');
            }
        }

        LmsAssignment::create([
            'lms_course_id' => $course->id,
            'lms_lesson_id' => $data['lms_lesson_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'max_score' => $data['max_score'] ?? LmsSettings::assignmentMaxScore(),
            'allow_late' => $request->boolean('allow_late', LmsSettings::allowLateByDefault()),
            'is_published' => $request->boolean('is_published', true),
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Đã tạo bài tập.');
    }

    public function submit(Request $request, LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureVisible($course);
        $this->ensureAssignment($course, $assignment);

        if (! $assignment->isOpen() && ! $assignment->allow_late) {
            return back()->with('error', 'Đã quá hạn nộp bài.');
        }

        $maxFileKilobytes = LmsSettings::submissionMaxMegabytes() * 1024;
        $data = $request->validate([
            'text_answer' => 'nullable|string|max:20000',
            'file' => 'nullable|file|max:'.$maxFileKilobytes,
        ]);

        $existing = LmsAssignmentSubmission::query()
            ->where('lms_assignment_id', $assignment->id)
            ->where('user_id', Auth::id())
            ->first();

        if (empty($data['text_answer']) && ! $request->hasFile('file') && ! ($existing?->file_path)) {
            return back()->with('error', 'Nhập nội dung hoặc đính kèm file.');
        }

        $path = $existing?->file_path;
        $fname = $existing?->file_name;
        $disk = $existing?->disk ?: 'public';
        if ($request->hasFile('file')) {
            // Bài nộp là dữ liệu riêng tư: luôn đi qua route có kiểm tra quyền,
            // không đặt dưới public/storage.
            $path = $request->file('file')->store('lms/courses/'.$course->id.'/submissions', 'local');
            $fname = $request->file('file')->getClientOriginalName();
            $disk = 'local';
        }

        // Sprint 8 G9 + Sprint 9 H3: versioning — giữ lịch sử lần nộp
        $wasGraded = $existing && $existing->status === 'graded';
        $text = $data['text_answer'] ?? $existing?->text_answer;

        // Snapshot version cũ trước khi ghi đè
        if ($existing && Schema::hasTable('lms_assignment_submission_versions')) {
            $this->snapshotVersion($existing);
        }

        $nextVer = $existing
            ? max(1, (int) ($existing->version_count ?? 1) + ($existing->submitted_at ? 1 : 0))
            : 1;

        $payload = [
            'text_answer' => $text,
            'file_path' => $path,
            'file_name' => $fname,
            'disk' => $disk,
            'submitted_at' => now(),
            'status' => 'submitted',
            'score' => null,
            'graded_by' => null,
            'graded_at' => null,
            'feedback' => null,
            'version_count' => $nextVer,
            'attempt_no' => $nextVer,
        ];

        $sub = LmsAssignmentSubmission::query()->updateOrCreate(
            ['lms_assignment_id' => $assignment->id, 'user_id' => Auth::id()],
            $payload
        );

        if (Schema::hasTable('lms_assignment_submission_versions')) {
            LmsAssignmentSubmissionVersion::query()->updateOrCreate(
                [
                    'lms_assignment_submission_id' => $sub->id,
                    'version_no' => $nextVer,
                ],
                [
                    'text_answer' => $text,
                    'file_path' => $path,
                    'file_name' => $fname,
                    'disk' => $disk,
                    'status' => 'submitted',
                    'score' => null,
                    'feedback' => null,
                    'submitted_at' => now(),
                    'graded_by' => null,
                    'graded_at' => null,
                ]
            );
        }

        return back()->with('success', $wasGraded
            ? "Đã nộp lại (phiên bản v{$nextVer}). Chờ GV chấm."
            : "Đã nộp bài (v{$nextVer}).");
    }

    /** Học viên tải lại file bài nộp của chính mình qua kiểm tra quyền. */
    public function downloadOwn(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureVisible($course);
        $this->ensureAssignment($course, $assignment);

        $submission = LmsAssignmentSubmission::query()
            ->where('lms_assignment_id', $assignment->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (! $submission->file_path) {
            return back()->with('error', 'Bài nộp không có file đính kèm.');
        }

        $disk = $submission->disk ?: 'local';
        if (! Storage::disk($disk)->exists($submission->file_path)) {
            return back()->with('error', 'File bài nộp không còn trên máy chủ.');
        }

        return Storage::disk($disk)->download(
            $submission->file_path,
            $submission->file_name ?: basename($submission->file_path)
        );
    }

    public function grade(Request $request, LmsCourse $course, LmsAssignment $assignment, LmsAssignmentSubmission $submission)
    {
        $this->ensureEdit($course);
        $this->ensureAssignment($course, $assignment);
        if ((int) $submission->lms_assignment_id !== (int) $assignment->id) {
            abort(404);
        }

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:'.$assignment->max_score,
            'feedback' => 'nullable|string|max:5000',
        ]);

        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'status' => 'graded',
            'graded_by' => Auth::id(),
            'graded_at' => now(),
        ]);

        // Cập nhật version hiện tại
        if (Schema::hasTable('lms_assignment_submission_versions')) {
            $verNo = (int) ($submission->version_count ?: $submission->attempt_no ?: 1);
            LmsAssignmentSubmissionVersion::query()->updateOrCreate(
                [
                    'lms_assignment_submission_id' => $submission->id,
                    'version_no' => $verNo,
                ],
                [
                    'text_answer' => $submission->text_answer,
                    'file_path' => $submission->file_path,
                    'file_name' => $submission->file_name,
                    'disk' => $submission->disk,
                    'status' => 'graded',
                    'score' => $data['score'],
                    'feedback' => $data['feedback'] ?? null,
                    'submitted_at' => $submission->submitted_at,
                    'graded_by' => Auth::id(),
                    'graded_at' => now(),
                ]
            );
        }

        return back()->with('success', 'Đã chấm bài.');
    }

    protected function snapshotVersion(LmsAssignmentSubmission $existing): void
    {
        if (! $existing->submitted_at && ! $existing->text_answer && ! $existing->file_path) {
            return;
        }
        $verNo = max(1, (int) ($existing->version_count ?: $existing->attempt_no ?: 1));
        LmsAssignmentSubmissionVersion::query()->updateOrCreate(
            [
                'lms_assignment_submission_id' => $existing->id,
                'version_no' => $verNo,
            ],
            [
                'text_answer' => $existing->text_answer,
                'file_path' => $existing->file_path,
                'file_name' => $existing->file_name,
                'disk' => $existing->disk ?: 'public',
                'status' => $existing->status,
                'score' => $existing->score,
                'feedback' => $existing->feedback,
                'submitted_at' => $existing->submitted_at,
                'graded_by' => $existing->graded_by,
                'graded_at' => $existing->graded_at,
            ]
        );
    }

    public function submissions(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureEdit($course);
        $this->ensureAssignment($course, $assignment);
        $subs = $assignment->submissions()->with('user')->orderByDesc('submitted_at')->get();

        return view('lms::assignments.submissions', compact('course', 'assignment', 'subs'));
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

    protected function ensureAssignment(LmsCourse $course, LmsAssignment $a): void
    {
        if ((int) $a->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }
}
