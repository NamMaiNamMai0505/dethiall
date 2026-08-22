<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Services\LmsCourseService;
use Modules\Subject\Models\SubjectLesson;

class LessonController extends Controller
{
    public function __construct(protected LmsCourseService $courseService)
    {
        $this->middleware(['auth']);
        $this->middleware(ApplicationGate::middleware('lms.lessons', ApplicationRegistry::ACTION_VIEW))->only(['index', 'show']);
        $this->middleware(ApplicationGate::middleware('lms.lessons', ApplicationRegistry::ACTION_EDIT))->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(LmsCourse $course)
    {
        $this->ensureCourseVisible($course);
        $course->load(['lessons.subjectLesson', 'subject']);

        return view('lms::lessons.index', compact('course'));
    }

    public function create(LmsCourse $course)
    {
        $this->ensureCourseManageable($course);
        $subjectLessons = SubjectLesson::query()
            ->where('subject_id', $course->subject_id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('lms::lessons.create', compact('course', 'subjectLessons'));
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureCourseManageable($course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'content' => 'nullable|string',
            'subject_lesson_id' => 'nullable|exists:subject_lessons,id',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'week_number' => 'nullable|integer|min:1|max:52',
            'is_published' => 'nullable|boolean',
        ]);

        $maxSort = (int) $course->lessons()->max('sort_order');

        LmsLesson::create([
            'lms_course_id' => $course->id,
            'subject_lesson_id' => $data['subject_lesson_id'] ?? null,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
            'week_number' => $data['week_number'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('lms.courses.lessons.index', $course)
            ->with('success', 'Đã thêm bài học trong khóa LMS.');
    }

    public function show(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureCourseVisible($course);
        $this->ensureLessonBelongs($course, $lesson);
        $lesson->load('subjectLesson');

        return view('lms::lessons.show', compact('course', 'lesson'));
    }

    public function edit(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureCourseManageable($course);
        $this->ensureLessonBelongs($course, $lesson);
        $subjectLessons = SubjectLesson::query()
            ->where('subject_id', $course->subject_id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('lms::lessons.edit', compact('course', 'lesson', 'subjectLessons'));
    }

    public function update(Request $request, LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureCourseManageable($course);
        $this->ensureLessonBelongs($course, $lesson);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'content' => 'nullable|string',
            'subject_lesson_id' => 'nullable|exists:subject_lessons,id',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'week_number' => 'nullable|integer|min:1|max:52',
            'is_published' => 'nullable|boolean',
        ]);

        $published = $request->boolean('is_published');
        $lesson->fill([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'subject_lesson_id' => $data['subject_lesson_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? $lesson->sort_order,
            'week_number' => $data['week_number'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? ($lesson->published_at ?? now()) : null,
            'updated_by' => Auth::id(),
        ]);
        $lesson->save();

        return redirect()
            ->route('lms.courses.lessons.show', [$course, $lesson])
            ->with('success', 'Đã cập nhật bài học LMS.');
    }

    public function destroy(LmsCourse $course, LmsLesson $lesson)
    {
        $this->ensureCourseManageable($course);
        $this->ensureLessonBelongs($course, $lesson);
        $lesson->delete();

        return redirect()
            ->route('lms.courses.lessons.index', $course)
            ->with('success', 'Đã xoá bài học LMS.');
    }

    protected function ensureCourseVisible(LmsCourse $course): void
    {
        if (! $this->courseService->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403, 'Không có quyền truy cập khóa học.');
        }
    }

    protected function ensureCourseManageable(LmsCourse $course): void
    {
        if (! Auth::user()?->can('lms.edit')) {
            abort(403);
        }
        $this->ensureCourseVisible($course);
    }

    protected function ensureLessonBelongs(LmsCourse $course, LmsLesson $lesson): void
    {
        if ((int) $lesson->lms_course_id !== (int) $course->id) {
            abort(404);
        }
    }
}
