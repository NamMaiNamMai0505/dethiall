<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Jobs\SendSystemNotificationEmail;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAssignmentSubmissionVersion;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Support\LmsAccess;
use Modules\Lms\Support\LmsSettings;
use ZipArchive;

/**
 * Sprint GV-3 — Giao bài tập + chấm điểm + tải bài nộp.
 */
class AssignmentController extends Controller
{
    public function __construct(protected LmsCourseService $courses)
    {
        $this->middleware(['auth', 'permission:lms.edit']);
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'due_at' => 'nullable|date',
            'max_score' => 'nullable|numeric|min:0|max:1000',
            'allow_late' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'lms_lesson_id' => 'nullable|integer|exists:lms_lessons,id',
        ]);

        if (! empty($data['lms_lesson_id'])) {
            if (! $course->lessons()->where('id', $data['lms_lesson_id'])->exists()) {
                return $this->backAssign($course, 'Bài học không thuộc khóa này.', true);
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

        return $this->backAssign($course, 'Đã tạo bài tập.');
    }

    public function update(Request $request, LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureTeach($course);
        $this->ensureAssignment($course, $assignment);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'due_at' => 'nullable|date',
            'max_score' => 'nullable|numeric|min:0|max:1000',
            'allow_late' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'lms_lesson_id' => 'nullable|integer|exists:lms_lessons,id',
        ]);

        if (! empty($data['lms_lesson_id'])) {
            if (! $course->lessons()->where('id', $data['lms_lesson_id'])->exists()) {
                return $this->backAssign($course, 'Bài học không thuộc khóa này.', true);
            }
        }

        $assignment->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'max_score' => $data['max_score'] ?? $assignment->max_score,
            'allow_late' => $request->boolean('allow_late'),
            'is_published' => $request->boolean('is_published'),
            'lms_lesson_id' => $data['lms_lesson_id'] ?? null,
        ]);

        return $this->backAssign($course, 'Đã cập nhật bài tập.');
    }

    public function destroy(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureTeach($course);
        $this->ensureAssignment($course, $assignment);
        $assignment->delete();

        return $this->backAssign($course, 'Đã xoá bài tập.');
    }

    public function toggle(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureTeach($course);
        $this->ensureAssignment($course, $assignment);
        $assignment->update(['is_published' => ! $assignment->is_published]);

        return $this->backAssign(
            $course,
            $assignment->is_published ? 'Đã công bố bài tập.' : 'Đã ẩn bài tập.'
        );
    }

    public function grade(Request $request, LmsCourse $course, LmsAssignment $assignment, LmsAssignmentSubmission $submission)
    {
        $this->ensureTeach($course);
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

        if (Schema::hasTable('lms_assignment_submission_versions')) {
            $verNo = max(1, (int) ($submission->version_count ?: $submission->attempt_no ?: 1));
            LmsAssignmentSubmissionVersion::query()->updateOrCreate(
                [
                    'lms_assignment_submission_id' => $submission->id,
                    'version_no' => $verNo,
                ],
                [
                    'text_answer' => $submission->text_answer,
                    'file_path' => $submission->file_path,
                    'file_name' => $submission->file_name,
                    'disk' => $submission->disk ?: 'public',
                    'status' => 'graded',
                    'score' => $data['score'],
                    'feedback' => $data['feedback'] ?? null,
                    'submitted_at' => $submission->submitted_at,
                    'graded_by' => Auth::id(),
                    'graded_at' => now(),
                ]
            );
        }

        $this->notifyStudentGraded($course, $assignment, $submission);

        return $this->backAssign($course, 'Đã chấm bài cho '.($submission->user->name ?? 'HV').'.');
    }

    /** Tải file nộp của 1 HV */
    public function downloadOne(LmsCourse $course, LmsAssignment $assignment, LmsAssignmentSubmission $submission)
    {
        $this->ensureTeach($course);
        $this->ensureAssignment($course, $assignment);
        if ((int) $submission->lms_assignment_id !== (int) $assignment->id) {
            abort(404);
        }
        if (! $submission->file_path) {
            return $this->backAssign($course, 'Bài nộp không có file đính kèm.', true);
        }
        $disk = $submission->disk ?: 'public';
        if (! Storage::disk($disk)->exists($submission->file_path)) {
            return $this->backAssign($course, 'File không còn trên máy chủ.', true);
        }
        $name = $submission->file_name
            ?: basename($submission->file_path);

        return Storage::disk($disk)->download($submission->file_path, $name);
    }

    /** Tải ZIP tất cả file nộp của 1 bài tập */
    public function downloadAll(LmsCourse $course, LmsAssignment $assignment)
    {
        $this->ensureTeach($course);
        $this->ensureAssignment($course, $assignment);

        $subs = $assignment->submissions()->with('user:id,name')->whereNotNull('file_path')->get();
        if ($subs->isEmpty()) {
            return $this->backAssign($course, 'Chưa có file nộp nào để tải.', true);
        }

        if (! class_exists(ZipArchive::class)) {
            return $this->backAssign($course, 'Máy chủ thiếu ZipArchive — không tạo được ZIP.', true);
        }

        $tmp = storage_path('app/tmp');
        if (! is_dir($tmp)) {
            mkdir($tmp, 0755, true);
        }
        $zipPath = $tmp.'/asg-'.$assignment->id.'-'.time().'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->backAssign($course, 'Không tạo được file ZIP.', true);
        }

        foreach ($subs as $sub) {
            $disk = $sub->disk ?: 'public';
            if (! Storage::disk($disk)->exists($sub->file_path)) {
                continue;
            }
            $raw = Storage::disk($disk)->get($sub->file_path);
            $safeUser = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $sub->user->name ?? ('user'.$sub->user_id));
            $fname = $sub->file_name ?: basename($sub->file_path);
            $zip->addFromString($safeUser.'_'.$sub->user_id.'_'.$fname, $raw);
        }
        // Thêm text answers
        $texts = $assignment->submissions()->with('user:id,name')->whereNotNull('text_answer')->get();
        if ($texts->isNotEmpty()) {
            $body = '';
            foreach ($texts as $t) {
                $body .= '=== '.($t->user->name ?? $t->user_id)." ===\n".($t->text_answer ?? '')."\n\n";
            }
            $zip->addFromString('_text_answers.txt', $body);
        }
        $zip->close();

        $downloadName = 'nop-bai-'.Str::slug($assignment->title, '-').'.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    protected function notifyStudentGraded(LmsCourse $course, LmsAssignment $assignment, LmsAssignmentSubmission $submission): void
    {
        try {
            if (! LmsSettings::notifyAssignmentGraded() || ! Schema::hasTable('system_notifications')) {
                return;
            }
            $student = User::query()->find($submission->user_id);
            if (! $student) {
                return;
            }
            // Sprint 8 T2: queue email sau khi insert chuông
            $notification = SystemNotification::query()->create([
                'user_id' => $student->id,
                'actor_id' => Auth::id(),
                'title' => 'Đã chấm bài tập',
                'message' => 'Bài «'.$assignment->title.'» đã được chấm: '.$submission->score.'/'.$assignment->max_score
                    .($submission->feedback ? ' — '.$submission->feedback : ''),
                'type' => 'lms_assignment_graded',
                'module' => 'lms',
                'action' => 'grade',
                'url' => '/lms/hoc/courses/'.$course->id.'?tab=assignments',
            ]);
            SendSystemNotificationEmail::dispatch($notification->id);
        } catch (\Throwable $e) {
        }
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

    protected function ensureAssignment(LmsCourse $course, LmsAssignment $a): void
    {
        if ((int) $a->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function backAssign(LmsCourse $course, string $message, bool $error = false)
    {
        return redirect()
            ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=assign')
            ->with($error ? 'error' : 'success', $message);
    }
}
