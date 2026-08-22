<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsGradebookService;
use Modules\Lms\Services\LmsGradeTransferService;
use Modules\Lms\Support\LmsAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsGradebookService $gradebook,
        protected LmsGradeTransferService $transfer,
    ) {
        $this->middleware('auth');
        $this->middleware(ApplicationGate::middleware('lms.gradebook', ApplicationRegistry::ACTION_VIEW));
        // Export multi-course: admin/manager (create or manage)
        $this->middleware(ApplicationGate::middleware('lms.gradebook', ApplicationRegistry::ACTION_EXPORT))->only(['exportMultiForm', 'exportMulti']);
    }

    /**
     * Sprint 8 M2 — form chọn nhiều khóa để export điểm.
     */
    public function exportMultiForm()
    {
        $courses = $this->courses->queryForUser()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'title', 'code', 'status', 'class_id', 'subject_id']);

        return view('lms::gradebook.export-multi', compact('courses'));
    }

    /**
     * Sprint 8 M2 — CSV điểm nhiều khóa (1 file).
     */
    public function exportMulti(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:lms_courses,id',
        ]);

        $ids = collect($data['course_ids'])->unique()->values();
        $visible = $this->courses->queryForUser()->whereIn('lms_courses.id', $ids)->pluck('id');
        if ($visible->isEmpty()) {
            abort(403, 'Không có khóa nào trong phạm vi của bạn.');
        }

        $filename = 'lms-grades-multi-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($visible) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'course_id', 'course_title', 'course_code',
                'user_id', 'student_name', 'student_email',
                'assignment_avg', 'exam_avg', 'attendance_pct', 'progress_pct',
                'computed_score', 'final_score', 'letter', 'note',
            ]);

            foreach ($visible as $cid) {
                $course = LmsCourse::query()->find($cid);
                if (! $course) {
                    continue;
                }
                $matrix = $this->gradebook->matrix($course);
                foreach ($matrix['rows'] as $uid => $row) {
                    $user = $row['user'] ?? null;
                    fputcsv($out, [
                        $course->id,
                        $course->title,
                        $course->code,
                        $uid,
                        $user->name ?? '',
                        $user->email ?? '',
                        $row['assignment_avg'],
                        $row['exam_avg'],
                        $row['attendance_pct'],
                        $row['progress_pct'],
                        $row['computed_score'],
                        $row['final_score'],
                        $row['letter'],
                        $row['note'],
                    ]);
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $matrix = $this->gradebook->matrix($course);

        $view = LmsAccess::usesAdminShell() || Auth::user()?->can('lms.edit')
            ? 'lms::gradebook.show'
            : 'lms::learn.gradebook';

        return view($view, [
            'course' => $course,
            'students' => $matrix['students'],
            'assignments' => $matrix['assignments'],
            'exams' => $matrix['exams'],
            'rows' => $matrix['rows'],
        ]);
    }

    public function refresh(LmsCourse $course)
    {
        $this->ensureEdit($course);
        $n = $this->gradebook->refreshStored($course);

        return $this->backGradebook($course, "Đã cập nhật bảng điểm ({$n} học viên).");
    }

    public function override(Request $request, LmsCourse $course, User $user)
    {
        $this->ensureEdit($course);
        $data = $request->validate([
            'final_score' => 'nullable|numeric|min:0|max:10',
            'note' => 'nullable|string|max:2000',
        ]);

        $this->gradebook->saveOverride(
            $course,
            $user,
            array_key_exists('final_score', $data) && $data['final_score'] !== null
                ? (float) $data['final_score']
                : null,
            $data['note'] ?? null
        );

        return $this->backGradebook($course, 'Đã lưu điểm tổng kết cho '.$user->name);
    }

    public function transferToGrades(Request $request, LmsCourse $course)
    {
        $this->ensureEdit($course);

        try {
            $book = $this->transfer->transfer($course, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('grades.books.show', $book)
            ->with('success', 'Đã chuyển snapshot điểm LMS sang bảng điểm nháp. Quy trình duyệt của Quản lý điểm được giữ nguyên.');
    }

    public function my(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $matrix = $this->gradebook->matrix($course, Auth::id());
        $row = $matrix['rows'][Auth::id()] ?? null;

        return view('lms::learn.gradebook', [
            'course' => $course,
            'students' => collect([Auth::user()]),
            'assignments' => $matrix['assignments'],
            'exams' => $matrix['exams'],
            'rows' => $row ? [Auth::id() => $row] : [],
            'mineOnly' => true,
        ]);
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
        // Portal GV: chỉ khóa mình phụ trách
        if ($user?->can('lms.edit') && LmsAccess::canTeachCourse($course, $user)) {
            return;
        }
        if (! $user?->can('lms.edit')) {
            abort(403);
        }
        $this->ensureVisible($course);
    }

    /** Admin back() · teach → tab Lớp học */
    protected function backGradebook(LmsCourse $course, string $message)
    {
        if (request()->boolean('teach')
            || request()->input('return') === 'teach'
            || str_contains((string) url()->previous(), 'mode=teach')) {
            return redirect()
                ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=class')
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
