<?php

namespace Modules\Subject\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TrainingDept;
use App\Support\TrainingScheduleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Exports\LessonsTemplateExport;
use Modules\Subject\Imports\LessonsImport;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\Subject\Support\ImportRuntime;
use Modules\Subject\Support\SubjectCodePrefix;

class SubjectLessonController extends Controller
{
    public function create(Request $request)
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $subjects = $this->scopedSubjects();
        $specializations = Specialization::query()->with('trainingSystem:id,name')->orderBy('name')->get();
        $subjectId = $request->integer('subject_id') ?: null;
        $selectedSubject = $subjectId
            ? $subjects->firstWhere('id', $subjectId)
            : null;
        if ($subjectId && ! $selectedSubject) {
            abort(404, 'Môn học không thuộc phạm vi quản lý của bạn.');
        }

        return view('subject::lessons.form', [
            'lesson' => (new SubjectLesson([
                'subject_id' => $selectedSubject?->id,
            ]))->setRelation('subject', $selectedSubject),
            'subjects' => $subjects,
            'specializations' => $specializations,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedLesson($request);
        TrainingScheduleAccess::ensureSubjectInScope((int) $data['subject_id']);
        $lesson = SubjectLesson::create($data);

        return redirect()
            ->route('subject-lessons.index', $this->filterContext($request, (int) $lesson->subject_id))
            ->with('success', 'Đã thêm bài học.');
    }

    /**
     * Giữ nguyên chuỗi lọc Hệ → Ngành → Môn khi quay lại danh sách, để thêm
     * nhiều bài liên tiếp không phải chọn lại bộ lọc sau mỗi lần lưu.
     *
     * @return array<string, int>
     */
    private function filterContext(Request $request, int $subjectId): array
    {
        $subject = Subject::query()
            ->with('specialization:id,training_system_id')
            ->find($subjectId);

        return array_filter([
            'training_system_id' => $request->integer('training_system_id')
                ?: (int) ($subject?->specialization?->training_system_id ?? 0),
            'specialization_id' => $request->integer('specialization_id')
                ?: (int) ($subject?->specialization_id ?? 0),
            'subject_id' => $subjectId,
        ]);
    }

    public function edit(SubjectLesson $subjectLesson)
    {
        TrainingScheduleAccess::ensureSubjectInScope((int) $subjectLesson->subject_id);
        $subjectLesson->load('subject.specialization.trainingSystem');
        $subjects = $this->scopedSubjects();

        // Môn của bài đang sửa có thể đã bị ngừng hoạt động sau khi bài được
        // tạo — scopedSubjects() chỉ lấy môn active nên sẽ thiếu chính môn
        // này, khiến ô "Môn học" không chọn được gì và kéo theo "Bài cha"
        // không nạp được. Luôn đảm bảo môn hiện tại có mặt trong danh sách.
        if ($subjectLesson->subject && ! $subjects->contains('id', $subjectLesson->subject_id)) {
            $subjects = $subjects->push($subjectLesson->subject)->sortBy('name')->values();
        }

        $specializations = Specialization::query()->with('trainingSystem:id,name')->orderBy('name')->get();

        return view('subject::lessons.form', [
            'lesson' => $subjectLesson,
            'subjects' => $subjects,
            'specializations' => $specializations,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, SubjectLesson $subjectLesson)
    {
        TrainingScheduleAccess::ensureSubjectInScope((int) $subjectLesson->subject_id);
        $data = $this->validatedLesson($request, $subjectLesson);
        TrainingScheduleAccess::ensureSubjectInScope((int) $data['subject_id']);
        $subjectLesson->update($data);

        return redirect()
            ->route('subject-lessons.index', $this->filterContext($request, (int) $subjectLesson->subject_id))
            ->with('success', 'Đã cập nhật bài học.');
    }

    public function destroy(SubjectLesson $subjectLesson)
    {
        TrainingScheduleAccess::ensureSubjectInScope((int) $subjectLesson->subject_id);
        if ($subjectLesson->children()->exists()) {
            return back()->with('error', 'Không thể xóa bài còn bài con. Hãy xóa bài con trước.');
        }
        $subjectLesson->delete();

        return back()->with('success', 'Đã xóa bài học.');
    }

    /**
     * Xóa nhiều bài học cùng lúc (chọn nhiều qua checkbox). Bài cha được
     * chọn sẽ kéo theo xóa toàn bộ bài con của nó.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một bài học để xóa.');
        }

        $lessons = SubjectLesson::whereIn('id', $ids)->get();
        $deleted = 0;

        foreach ($lessons as $lesson) {
            TrainingScheduleAccess::ensureSubjectInScope((int) $lesson->subject_id);
            $this->deleteLessonWithChildren($lesson);
            $deleted++;
        }

        return back()->with('success', "Đã xóa {$deleted} bài học đã chọn (kèm bài con nếu có).");
    }

    private function deleteLessonWithChildren(SubjectLesson $lesson): void
    {
        $lesson->children()->get()->each(fn (SubjectLesson $child) => $this->deleteLessonWithChildren($child));
        $lesson->delete();
    }

    private function scopedSubjects()
    {
        $query = Subject::query()->active()->with('specialization.trainingSystem')->orderBy('name');
        TrainingDept::applySubjectFacultyScope($query);

        return $query->get(['id', 'name', 'code', 'specialization_id']);
    }

    private function validatedLesson(Request $request, ?SubjectLesson $lesson = null): array
    {
        $data = $request->validate([
            'specialization_id' => ['required', 'exists:specializations,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'parent_id' => ['nullable', 'exists:subject_lessons,id'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'lesson_kind' => ['required', 'in:unit,chapter,lesson,sub,exam'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'theory_hours' => ['nullable', 'integer', 'min:0'],
            'practice_hours' => ['nullable', 'integer', 'min:0'],
            'exam_hours' => ['nullable', 'integer', 'min:0'],
            'total_hours' => ['nullable', 'integer', 'min:0'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $subject = Subject::query()->whereKey($data['subject_id'])->firstOrFail();
        abort_unless((int) $subject->specialization_id === (int) $data['specialization_id'], 422, 'Môn học không thuộc ngành đã chọn.');
        if (! empty($data['parent_id'])) {
            $parent = SubjectLesson::query()->findOrFail($data['parent_id']);
            abort_unless((int) $parent->subject_id === (int) $data['subject_id'], 422, 'Bài cha phải thuộc cùng môn học.');
        }

        $exists = SubjectLesson::query()->where('subject_id', $data['subject_id'])
            ->where('code', $data['code'])
            ->when($lesson, fn ($q) => $q->where('id', '!=', $lesson->id))
            ->exists();
        if ($exists) {
            abort(422, 'Mã bài đã tồn tại trong môn học này.');
        }

        unset($data['specialization_id']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['theory_hours'] = (int) ($data['theory_hours'] ?? 0);
        $data['practice_hours'] = (int) ($data['practice_hours'] ?? 0);
        $data['exam_hours'] = (int) ($data['exam_hours'] ?? 0);
        $data['total_hours'] = (int) ($data['total_hours'] ?? ($data['theory_hours'] + $data['practice_hours'] + $data['exam_hours']));

        return $data;
    }

    public function index(Request $request)
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $user = Auth::user();
        $query = SubjectLesson::query()
            ->with(['subject.specialization', 'parent', 'children'])
            ->whereNull('parent_id')
            ->orderBy('subject_id')
            ->orderBy('sort_order');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }
        if ($request->filled('specialization_id')) {
            $query->whereHas('subject', fn ($q) => $q->where('specialization_id', $request->integer('specialization_id')));
        }
        if ($request->filled('training_system_id')) {
            $query->whereHas(
                'subject.specialization',
                fn ($q) => $q->where('training_system_id', $request->integer('training_system_id'))
            );
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('code', 'like', $term);
            });
        }

        // Khoa: chỉ bài của môn thuộc khoa (hậu tố mã môn = K1…K8)
        if (TrainingDept::isFacultyManager($user)) {
            $query->whereHas('subject', function ($q) use ($user) {
                TrainingDept::applySubjectFacultyScope($q, $user);
            });
        }

        // Chưa chọn Môn và cũng không tìm kiếm -> hiển thị "hub" các Môn thay
        // vì liệt kê phẳng bài học của nhiều môn cùng lúc (dễ rối mắt).
        // Không cần chạy query bài học trong trường hợp này.
        $showSubjectHub = ! $request->filled('subject_id') && ! $request->filled('q');
        $lessons = $showSubjectHub ? null : $query->paginate(40)->withQueryString();

        // Lọc liên động: Hệ → Ngành → Môn. Mỗi cấp chỉ liệt kê lựa chọn hợp lệ
        // với cấp trước, nên không còn chọn được tổ hợp Ngành/Môn không khớp.
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');

        $specializationQuery = Specialization::query()->with('trainingSystem:id,name')->orderBy('name');
        if ($request->filled('training_system_id')) {
            $specializationQuery->where('training_system_id', $request->integer('training_system_id'));
        }
        $specializations = $specializationQuery->get(['id', 'name', 'code', 'major_code', 'level', 'training_form', 'training_system_id']);

        $subjectsQuery = Subject::query()->active()
            ->with('specialization.trainingSystem:id,name')
            ->withCount(['lessons as lessons_count' => fn ($q) => $q->whereNull('parent_id')])
            ->orderBy('name');
        TrainingDept::applySubjectFacultyScope($subjectsQuery);
        if ($request->filled('specialization_id')) {
            $subjectsQuery->where('specialization_id', $request->integer('specialization_id'));
        } elseif ($request->filled('training_system_id')) {
            $subjectsQuery->whereIn('specialization_id', $specializations->pluck('id'));
        }
        $subjects = $subjectsQuery->get(['id', 'name', 'code', 'specialization_id']);

        $selectedSubject = $request->filled('subject_id')
            ? $subjects->firstWhere('id', $request->integer('subject_id'))
            : null;

        return view('subject::lessons.index', compact(
            'lessons',
            'subjects',
            'specializations',
            'trainingSystems',
            'selectedSubject',
            'showSubjectHub'
        ));
    }

    public function importForm()
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $subjects = $this->scopedSubjects();
        $specializations = Specialization::query()->with('trainingSystem:id,name')->orderBy('name')->get();

        return view('subject::lessons.import', [
            'subjects' => $subjects,
            'specializations' => $specializations,
        ]);
    }

    public function downloadLessonsTemplate()
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();

        return Excel::download(new LessonsTemplateExport, 'mau-import-bai-hoc.xlsx');
    }

    /**
     * Import bài học (đơn giản) - không phụ thuộc khung CTĐT nguyên khối.
     * Bắt buộc chọn Ngành → Môn trước khi import (đúng môn đang chọn);
     * cột "Mã môn học" trong file dùng để đối chiếu - dòng nào không khớp
     * môn đã chọn sẽ báo lỗi thay vì âm thầm ghi nhầm sang môn khác.
     * Xem Modules\Subject\Imports\LessonsImport.
     */
    public function import(Request $request)
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $importId = ImportRuntime::resolveId($request->header('X-Import-ID'));

        try {
            $validated = $request->validate([
                'specialization_id' => ['required', 'exists:specializations,id'],
                'subject_id' => ['required', 'exists:subjects,id'],
                // XLSX thường được máy chủ nhận diện là application/zip; kiểm tra theo
                // phần mở rộng rồi để PhpSpreadsheet xác thực nội dung workbook.
                'file' => 'required|file|extensions:xlsx,xls|max:20480',
            ], [
                'specialization_id.required' => 'Vui lòng chọn ngành đào tạo.',
                'subject_id.required' => 'Vui lòng chọn môn học.',
                'file.required' => 'Vui lòng chọn file bài học (xlsx).',
                'file.extensions' => 'Chỉ chấp nhận file Excel XLS hoặc XLSX.',
                'file.max' => 'File Excel không được lớn hơn 20 MB.',
            ]);

            $subject = Subject::query()->findOrFail($validated['subject_id']);
            TrainingScheduleAccess::ensureSubjectInScope($subject);
            $import = new LessonsImport($subject);

            ImportRuntime::begin($importId, [
                'import_type' => 'lessons',
                'user_id' => Auth::id(),
                'subject_id' => $subject->id,
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'file_size' => $request->file('file')?->getSize(),
            ]);

            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $message = sprintf(
                'Import xong: tạo %d bài học, cập nhật %d, bỏ qua %d.',
                $import->getImportedCount(),
                $import->getUpdatedCount(),
                $import->getSkippedCount()
            );
            if ($errors) {
                $message .= ' Có '.count($errors).' dòng lỗi: '.implode(' | ', array_slice($errors, 0, 5))
                    .(count($errors) > 5 ? ' ...' : '');
            }
            ImportRuntime::finish(true);

            if ($request->expectsJson()) {
                return ImportRuntime::jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'errors' => $errors,
                    'redirect_url' => route('subject-lessons.index'),
                ], 200, $importId);
            }

            return redirect()
                ->route('subject-lessons.index')
                ->with($errors ? 'error' : 'success', $message);
        } catch (ValidationException $e) {
            ImportRuntime::finish(false, $e);

            if (! $request->expectsJson()) {
                throw $e;
            }

            return ImportRuntime::jsonResponse([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
                    ?? 'Dữ liệu import chưa hợp lệ.',
                'errors' => $e->errors(),
            ], 422, $importId);
        } catch (\Throwable $e) {
            ImportRuntime::finish(false, $e);
            try {
                Log::error('Lessons import failed.', [
                    'import_id' => $importId,
                    'user_id' => Auth::id(),
                    'file_name' => $request->file('file')?->getClientOriginalName(),
                    'file_size' => $request->file('file')?->getSize(),
                    'exception' => $e,
                ]);
            } catch (\Throwable) {
                // Logging must never replace the import response with another 500.
            }

            $errorMessage = 'Import thất bại: '.ImportRuntime::safeExceptionMessage($e)
                .' (Mã lỗi: '.$importId.')';
            if ($request->expectsJson()) {
                return ImportRuntime::jsonResponse([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422, $importId);
            }

            return back()->withInput()->with('error', $errorMessage);
        }
    }

    public function bySubject(Request $request)
    {
        TrainingScheduleAccess::ensureValidRoleConfiguration();
        $subjectId = $request->integer('subject_id');
        if (! $subjectId) {
            return response()->json(['units' => [], 'lessons' => []]);
        }

        TrainingScheduleAccess::ensureSubjectInScope($subjectId);

        $roots = SubjectLesson::query()
            ->where('subject_id', $subjectId)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $units = $roots->filter(fn ($l) => $l->lesson_kind === SubjectLesson::KIND_UNIT || $l->isUnit())
            ->values()
            ->map(fn ($l) => [
                'id' => $l->id,
                'label' => $l->display_label,
                'kind' => $l->lesson_kind,
                'children' => $l->children->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->display_label,
                    'kind' => $c->lesson_kind,
                ])->values(),
            ]);

        $flatLessons = $roots->map(fn ($l) => [
            'id' => $l->id,
            'label' => $l->display_label,
            'kind' => $l->lesson_kind,
            'is_unit' => $l->lesson_kind === SubjectLesson::KIND_UNIT || $l->isUnit(),
            'children' => $l->children->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->display_label,
            ])->values(),
        ])->values();

        return response()->json([
            'units' => $units,
            'lessons' => $flatLessons,
        ]);
    }
}
