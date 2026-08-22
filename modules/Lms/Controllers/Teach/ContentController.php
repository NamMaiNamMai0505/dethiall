<?php

namespace Modules\Lms\Controllers\Teach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsScormPackage;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsMaterialService;
use Modules\Lms\Support\LmsAccess;
use Modules\Subject\Models\SubjectLesson;

/**
 * Sprint GV-2 — Soạn bài học + tài liệu ngay portal GV (mode teach).
 */
class ContentController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsMaterialService $materials,
    ) {
        $this->middleware(['auth', 'permission:lms.edit']);
    }

    public function storeLesson(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'content' => 'nullable|string',
            'week_number' => 'nullable|integer|min:1|max:52',
            'is_published' => 'nullable|boolean',
            // Sprint 8 G8: map khung CTĐT
            'subject_lesson_id' => 'nullable|integer|exists:subject_lessons,id',
        ]);

        if (! empty($data['subject_lesson_id']) && $course->subject_id) {
            $ok = SubjectLesson::query()
                ->where('id', $data['subject_lesson_id'])
                ->where('subject_id', $course->subject_id)
                ->exists();
            if (! $ok) {
                return $this->backAuthor($course, 'Bài CTĐT không thuộc môn của khóa này.', true);
            }
        }

        $maxSort = (int) $course->lessons()->max('sort_order');
        $published = $request->boolean('is_published');

        LmsLesson::create([
            'lms_course_id' => $course->id,
            'subject_lesson_id' => $data['subject_lesson_id'] ?? null,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'sort_order' => $maxSort + 1,
            'week_number' => $data['week_number'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? now() : null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return $this->backAuthor($course, 'Đã thêm bài học.');
    }

    public function updateLesson(Request $request, LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureTeach($course);
        $this->ensureLesson($course, $lesson);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'content' => 'nullable|string',
            'week_number' => 'nullable|integer|min:1|max:52',
            'is_published' => 'nullable|boolean',
            'subject_lesson_id' => 'nullable|integer|exists:subject_lessons,id',
        ]);

        if (! empty($data['subject_lesson_id']) && $course->subject_id) {
            $ok = SubjectLesson::query()
                ->where('id', $data['subject_lesson_id'])
                ->where('subject_id', $course->subject_id)
                ->exists();
            if (! $ok) {
                return $this->backAuthor($course, 'Bài CTĐT không thuộc môn của khóa này.', true);
            }
        }

        $published = $request->boolean('is_published');
        $lesson->fill([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'week_number' => $data['week_number'] ?? null,
            'subject_lesson_id' => $data['subject_lesson_id'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? ($lesson->published_at ?? now()) : null,
            'updated_by' => Auth::id(),
        ]);
        $lesson->save();

        return $this->backAuthor($course, 'Đã cập nhật bài học.');
    }

    public function destroyLesson(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureTeach($course);
        $this->ensureLesson($course, $lesson);
        $lesson->delete();

        return $this->backAuthor($course, 'Đã xoá bài học.');
    }

    public function toggleLesson(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureTeach($course);
        $this->ensureLesson($course, $lesson);

        $published = ! $lesson->is_published;
        $lesson->update([
            'is_published' => $published,
            'published_at' => $published ? ($lesson->published_at ?? now()) : null,
            'updated_by' => Auth::id(),
        ]);

        return $this->backAuthor($course, $published ? 'Đã công bố bài học.' : 'Đã ẩn bài học.');
    }

    public function moveLesson(Request $request, LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureTeach($course);
        $this->ensureLesson($course, $lesson);

        $dir = $request->validate(['direction' => 'required|in:up,down'])['direction'];
        $lessons = $course->lessons()->orderBy('sort_order')->orderBy('id')->get();
        $idx = $lessons->search(fn ($l) => (int) $l->id === (int) $lesson->id);
        if ($idx === false) {
            return $this->backAuthor($course, 'Không tìm thấy bài.', true);
        }

        $swapIdx = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($swapIdx < 0 || $swapIdx >= $lessons->count()) {
            return $this->backAuthor($course, 'Không thể di chuyển thêm.');
        }

        $other = $lessons[$swapIdx];
        $a = $lesson->sort_order;
        $b = $other->sort_order;
        // Nếu sort_order trùng, gán theo index
        if ($a === $b) {
            $a = $idx + 1;
            $b = $swapIdx + 1;
        }
        $lesson->update(['sort_order' => $b, 'updated_by' => Auth::id()]);
        $other->update(['sort_order' => $a, 'updated_by' => Auth::id()]);

        return $this->backAuthor($course, 'Đã sắp xếp lại thứ tự bài.');
    }

    public function storeMaterial(Request $request, LmsCourse $course)
    {
        $this->ensureTeach($course);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lms_lesson_id' => 'nullable|exists:lms_lessons,id',
            'file' => 'required|file|max:'.(LmsMaterialService::MAX_MB * 1024),
            'as_scorm' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        if (! empty($data['lms_lesson_id'])) {
            $ok = $course->lessons()->where('id', $data['lms_lesson_id'])->exists();
            if (! $ok) {
                return $this->backAuthor($course, 'Bài học không thuộc khóa này.', true);
            }
        }

        $file = $request->file('file');
        $meta = [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'lms_lesson_id' => $data['lms_lesson_id'] ?? null,
            'is_published' => $request->boolean('is_published', true),
        ];

        try {
            if ($request->boolean('as_scorm')) {
                $this->materials->storeScorm($course, $file, $meta);

                return $this->backAuthor($course, 'Đã tải lên gói SCORM.');
            }
            $this->materials->storeFile($course, $file, $meta);

            return $this->backAuthor($course, 'Đã tải lên tài liệu.');
        } catch (\Throwable $e) {
            return $this->backAuthor($course, 'Upload thất bại: '.$e->getMessage(), true);
        }
    }

    public function destroyMaterial(LmsCourse $course, LmsMaterial $material)
    {
        $this->ensureTeach($course);
        if ((int) $material->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $this->materials->deleteMaterial($material);

        return $this->backAuthor($course, 'Đã xoá tài liệu.');
    }

    public function destroyScorm(LmsCourse $course, LmsScormPackage $scorm)
    {
        $this->ensureTeach($course);
        if ((int) $scorm->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $this->materials->deleteScorm($scorm);

        return $this->backAuthor($course, 'Đã xoá gói SCORM.');
    }

    public function toggleMaterial(LmsCourse $course, LmsMaterial $material)
    {
        $this->ensureTeach($course);
        if ((int) $material->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $material->update(['is_published' => ! $material->is_published]);

        return $this->backAuthor(
            $course,
            $material->is_published ? 'Đã công bố tài liệu.' : 'Đã ẩn tài liệu.'
        );
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

    protected function ensureLesson(LmsCourse $course, LmsLesson $lesson): void
    {
        if ((int) $lesson->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }

    protected function backAuthor(LmsCourse $course, string $message, bool $error = false)
    {
        return redirect()
            ->to(route('lms.learn.courses.show', $course).'?mode=teach&tab=author')
            ->with($error ? 'error' : 'success', $message);
    }
}
