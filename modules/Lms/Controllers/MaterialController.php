<?php

namespace Modules\Lms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApplicationGate;
use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsScormPackage;
use Modules\Lms\Services\LmsCourseService;
use Modules\Lms\Services\LmsMaterialService;

class MaterialController extends Controller
{
    public function __construct(
        protected LmsCourseService $courses,
        protected LmsMaterialService $materials,
    ) {
        $this->middleware(['auth']);
        $this->middleware(ApplicationGate::middleware('lms.materials', ApplicationRegistry::ACTION_EDIT))->except(['index', 'download']);
        $this->middleware(ApplicationGate::middleware('lms.materials', ApplicationRegistry::ACTION_VIEW))->only(['index', 'download']);
    }

    public function index(LmsCourse $course)
    {
        $this->ensureVisible($course);
        $course->load(['materials.uploader', 'scormPackages', 'lessons']);

        return view('lms::materials.index', compact('course'));
    }

    public function store(Request $request, LmsCourse $course)
    {
        $this->ensureManage($course);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lms_lesson_id' => 'nullable|exists:lms_lessons,id',
            'file' => 'required|file|max:'.(LmsMaterialService::MAX_MB * 1024),
            'as_scorm' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

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

                return back()->with('success', 'Đã tải lên và bung gói SCORM.');
            }

            $this->materials->storeFile($course, $file, $meta);

            return back()->with('success', 'Đã tải lên tài liệu.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Upload thất bại: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(LmsCourse $course, LmsMaterial $material)
    {
        $this->ensureManage($course);
        if ((int) $material->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $this->materials->deleteMaterial($material);

        return back()->with('success', 'Đã xoá tài liệu.');
    }

    public function destroyScorm(LmsCourse $course, LmsScormPackage $scorm)
    {
        $this->ensureManage($course);
        if ((int) $scorm->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $this->materials->deleteScorm($scorm);

        return back()->with('success', 'Đã xoá gói SCORM và dữ liệu đã giải nén.');
    }

    public function download(LmsCourse $course, LmsMaterial $material)
    {
        $this->ensureVisible($course);
        if ((int) $material->lms_course_id !== (int) $course->id) {
            abort(404);
        }
        $url = $material->url();
        if (! $url) {
            return back()->with('error', 'File không tồn tại.');
        }

        return redirect()->away($url);
    }

    protected function ensureVisible(LmsCourse $course): void
    {
        if (! $this->courses->queryForUser()->where('lms_courses.id', $course->id)->exists()) {
            abort(403);
        }
    }

    protected function ensureManage(LmsCourse $course): void
    {
        if (! auth()->user()?->can('lms.edit')) {
            abort(403);
        }
        $this->ensureVisible($course);
    }
}
