<?php

namespace Modules\Subject\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\ManagerUnitScope;
use App\Support\TrainingDept;
use App\Support\TrainingScheduleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Subject\Exports\SubjectsTemplateExport;
use Modules\Subject\Imports\SubjectsImport;
use Modules\Subject\Models\Subject;
use Modules\Subject\Requests\AssignInstructorsRequest;
use Modules\Subject\Requests\CreateSubjectRequest;
use Modules\Subject\Requests\UpdateSubjectRequest;
use Modules\Subject\Services\CurriculumFrameworkImportService;
use Modules\Subject\Support\ImportRuntime;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\Unit\Models\Unit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SubjectController extends ModuleBaseController
{
    /**
     * Display a listing of subjects
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = Subject::with(['specialization', 'creator', 'updater']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Lọc theo Hệ → Ngành
        if ($request->filled('training_system_id')) {
            $query->whereHas('specialization', fn ($q) => $q->where('training_system_id', $request->integer('training_system_id')));
        }

        // Apply specialization filter
        if ($request->filled('specialization_id')) {
            $query->bySpecialization($request->specialization_id);
        }

        // Apply level filter
        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        // Apply assessment method filter
        if ($request->filled('assessment_method')) {
            $query->byAssessmentMethod($request->assessment_method);
        }

        // Apply semester filter
        if ($request->filled('semester')) {
            $query->bySemester($request->semester);
        }

        // Apply subject type filter
        if ($request->filled('subject_type')) {
            if ($request->subject_type === 'required') {
                $query->required();
            } elseif ($request->subject_type === 'elective') {
                $query->elective();
            }
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Khoa: chỉ môn có mã kết thúc …K{n} khớp unit (vd K7 Điều dưỡng)
        TrainingDept::applySubjectFacultyScope($query);

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10; // Default to 10 if invalid
        }
        $subjects = $query->paginate($perPage)->appends(request()->query());

        // Get filter options
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');
        $specializationQuery = Specialization::active()->orderBy('name');
        if ($request->filled('training_system_id')) {
            $specializationQuery->where('training_system_id', $request->integer('training_system_id'));
        }
        $specializations = $specializationQuery->pluck('name', 'id');
        $levels = [
            'basic' => 'Cơ bản',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Nâng cao',
        ];
        $assessmentMethods = [
            'exam' => 'Thi viết',
            'multiple_choice_questions' => 'Thi trắc nghiệm',
            'assignment' => 'Bài tập',
            'project' => 'Đồ án',
            'combined' => 'Tổng hợp',
        ];
        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'semester_3' => 'Học kỳ 3',
            'semester_4' => 'Học kỳ 4',
            'semester_5' => 'Học kỳ 5',
            'semester_6' => 'Học kỳ 6',
            'summer' => 'Học kỳ hè',
        ];

        return view('subject::index', compact('subjects', 'specializations', 'trainingSystems', 'levels', 'assessmentMethods', 'semesters'));
    }

    /**
     * Show the form for creating a new subject
     */
    public function create()
    {
        // Permission already checked by middleware
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');
        $specializationList = Specialization::active()
            ->with('trainingSystem:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'major_code', 'level', 'training_form', 'training_system_id']);
        $specializations = $specializationList->mapWithKeys(fn ($item) => [$item->id => $item->selection_label]);
        $specializationsBySystem = $specializationList->groupBy('training_system_id')
            ->map(fn ($items) => $items->mapWithKeys(fn ($s) => [$s->id => $s->selection_label]));
        $specializationPrefixMap = $specializationList->mapWithKeys(
            fn (Specialization $item) => [$item->id => SubjectCodePrefix::suggest($item)]
        );
        $codePrefixNote = SubjectCodePrefix::conventionNote();
        $facultyUnits = Unit::query()
            ->where('functional_type', Unit::FUNCTIONAL_FACULTY)
            ->whereNotNull('faculty_code')
            ->orderBy('faculty_code')
            ->get(['id', 'faculty_code', 'name', 'abbreviation']);
        $levels = [
            'basic' => 'Cơ bản',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Nâng cao',
        ];
        $assessmentMethods = [
            'exam' => 'Thi viết',
            'multiple_choice_questions' => 'Thi trắc nghiệm',
            'assignment' => 'Bài tập',
            'project' => 'Đồ án',
            'combined' => 'Tổng hợp',
        ];
        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'semester_3' => 'Học kỳ 3',
            'semester_4' => 'Học kỳ 4',
            'semester_5' => 'Học kỳ 5',
            'semester_6' => 'Học kỳ 6',
            'summer' => 'Học kỳ hè',
        ];

        return view('subject::create', compact(
            'trainingSystems',
            'specializations',
            'specializationsBySystem',
            'specializationPrefixMap',
            'codePrefixNote',
            'facultyUnits',
            'levels',
            'assessmentMethods',
            'semesters'
        ));
    }

    /**
     * Store a newly created subject
     */
    public function store(CreateSubjectRequest $request)
    {
        // Permission already checked by middleware
        $data = $request->validated();
        $specialization = Specialization::find($data['specialization_id'] ?? null);
        $prefix = SubjectCodePrefix::suggest($specialization);

        if (! empty($data['code'])) {
            $data['code'] = SubjectCodePrefix::buildSubjectCode($prefix, $data['code']);
        } else {
            $data['code'] = SubjectCodePrefix::buildSubjectCode(
                $prefix,
                $this->generateUniqueCode($data['name'])
            );
        }

        // Ensure unique after prefixing
        if (Subject::where('code', $data['code'])->exists()) {
            $data['code'] = $this->generateUniqueCode($data['code']);
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $subject = Subject::create($data);
        app(LmsCourseProvisioningService::class)->provisionForSpecialization(
            (int) $subject->specialization_id,
            $request->user()
        );

        return redirect()
            ->route('subjects.show', $subject->id)
            ->with('success', 'Môn học đã được tạo thành công!');
    }

    /**
     * Display the specified subject
     */
    public function show(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $subject->load(['specialization', 'creator', 'updater', 'instructors.unit', 'lessons']);

        return view('subject::show', compact('subject'));
    }

    /**
     * Mẫu Excel import Chi tiết môn học (bài học).
     * Cột chính: Mã bài học. Ngành/môn chọn trên form trước khi import.
     */
    public function downloadLessonsTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bài học');
        $sheet->fromArray([
            ['Mã bài học', 'Tên bài học (tuỳ chọn)', 'Thứ tự'],
            ['BH01', 'Bài mở đầu', 1],
            ['BH02', 'Bài 2', 2],
        ], null, 'A1');

        $fileName = 'template_chi_tiet_mon_hoc_'.date('Y-m-d').'.xlsx';
        $tmp = storage_path('app/import-templates/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tmp))) {
            mkdir(dirname($tmp), 0755, true);
        }
        try {
            (new Xlsx($spreadsheet))->save($tmp);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Import bài học (Chi tiết môn học) — bắt buộc chọn ngành + môn trước.
     */
    public function importLessons(Request $request, CurriculumFrameworkImportService $service)
    {
        $importId = ImportRuntime::resolveId($request->header('X-Import-ID'));

        try {
            $request->validate([
                'specialization_id' => ['required', 'exists:specializations,id'],
                'subject_id' => ['required', 'exists:subjects,id'],
                // Không dùng "mimes": một số file XLSX hợp lệ bị hệ điều hành nhận là
                // application/zip. Reader bên dưới sẽ kiểm tra nội dung workbook thật.
                'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:10240'],
                'lessons' => ['nullable', 'array'], // optional JSON preview path
            ], [
                'specialization_id.required' => 'Vui lòng chọn ngành đào tạo.',
                'subject_id.required' => 'Vui lòng chọn môn học.',
                'file.required' => 'Vui lòng chọn file Excel.',
                'file.extensions' => 'Chỉ chấp nhận file Excel XLS hoặc XLSX.',
                'file.max' => 'File Excel không được lớn hơn 10 MB.',
            ]);

            ImportRuntime::begin($importId, [
                'import_type' => 'subject_lessons',
                'user_id' => Auth::id(),
                'specialization_id' => $request->integer('specialization_id'),
                'subject_id' => $request->integer('subject_id'),
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'file_size' => $request->file('file')?->getSize(),
            ]);

            $subject = Subject::query()
                ->where('id', $request->integer('subject_id'))
                ->where('specialization_id', $request->integer('specialization_id'))
                ->firstOrFail();
            TrainingScheduleAccess::ensureSubjectInScope($subject);
            $stats = $service->importSubjectLessons(
                $request->file('file')->getRealPath(),
                $subject
            );
            $message = "Import Chi tiết môn học xong: thêm {$stats['imported']}, cập nhật {$stats['updated']}, bỏ qua {$stats['skipped']}.";
            ImportRuntime::finish(true);

            if ($request->expectsJson()) {
                $lessons = $subject->lessons()
                    ->get(['id', 'subject_id', 'code', 'name', 'sort_order'])
                    ->map(fn ($lesson) => [
                        'id' => $lesson->id,
                        'code' => $lesson->code,
                        'name' => $lesson->name,
                        'sort_order' => $lesson->sort_order,
                    ])
                    ->values();

                return ImportRuntime::jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'stats' => $stats,
                    'lessons' => $lessons,
                ], 200, $importId);
            }

            return redirect()
                ->route('subjects.show', $subject)
                ->with('success', $message);
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
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            ImportRuntime::finish(false, $e);
            try {
                Log::error('Subject lesson import failed.', [
                    'import_id' => $importId,
                    'user_id' => Auth::id(),
                    'specialization_id' => $request->integer('specialization_id'),
                    'subject_id' => $request->integer('subject_id'),
                    'file_name' => $request->file('file')?->getClientOriginalName(),
                    'file_size' => $request->file('file')?->getSize(),
                    'exception' => $e,
                ]);
            } catch (\Throwable) {
                // Logging must never replace the import response with another 500.
            }

            $errorMessage = 'Không import được file: '.ImportRuntime::safeExceptionMessage($e)
                .' (Mã lỗi: '.$importId.')';
            if ($request->expectsJson()) {
                return ImportRuntime::jsonResponse([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422, $importId);
            }

            return redirect()
                ->back()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Show the form for editing the specified subject
     */
    public function edit(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');
        $specializationList = Specialization::active()->with('trainingSystem:id,name')->orderBy('name')
            ->get(['id', 'name', 'code', 'major_code', 'level', 'training_form', 'training_system_id']);
        $specializations = $specializationList->mapWithKeys(fn ($item) => [$item->id => $item->selection_label]);
        $specializationsBySystem = $specializationList->groupBy('training_system_id')
            ->map(fn ($items) => $items->mapWithKeys(fn ($s) => [$s->id => $s->selection_label]));
        $facultyUnits = Unit::query()
            ->where('functional_type', Unit::FUNCTIONAL_FACULTY)
            ->whereNotNull('faculty_code')
            ->orderBy('faculty_code')
            ->get(['id', 'faculty_code', 'name', 'abbreviation']);
        $levels = [
            'basic' => 'Cơ bản',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Nâng cao',
        ];
        $assessmentMethods = [
            'exam' => 'Thi viết',
            'multiple_choice_questions' => 'Thi trắc nghiệm',
            'assignment' => 'Bài tập',
            'project' => 'Đồ án',
            'combined' => 'Tổng hợp',
        ];
        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'semester_3' => 'Học kỳ 3',
            'semester_4' => 'Học kỳ 4',
            'semester_5' => 'Học kỳ 5',
            'semester_6' => 'Học kỳ 6',
            'summer' => 'Học kỳ hè',
        ];

        return view('subject::edit', compact(
            'subject',
            'trainingSystems',
            'specializations',
            'specializationsBySystem',
            'facultyUnits',
            'levels',
            'assessmentMethods',
            'semesters'
        ));
    }

    /**
     * Update the specified subject
     */
    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $data = $request->validated();
        $previousSpecializationId = (int) $subject->specialization_id;
        $specialization = Specialization::find($data['specialization_id'] ?? null);
        if (! empty($data['code']) && $specialization) {
            $data['code'] = SubjectCodePrefix::buildSubjectCode(
                SubjectCodePrefix::suggest($specialization),
                SubjectCodePrefix::localCode($data['code'])
            );
        }
        $data['updated_by'] = Auth::id();
        $subject->update($data);
        $provisioning = app(LmsCourseProvisioningService::class);
        $provisioning->provisionForSpecialization((int) $subject->specialization_id, $request->user());
        if ($previousSpecializationId !== (int) $subject->specialization_id) {
            $provisioning->provisionForSpecialization($previousSpecializationId, $request->user());
        }

        return redirect()
            ->route('subjects.show', $subject->id)
            ->with('success', 'Môn học đã được cập nhật thành công!');
    }

    /**
     * Remove the specified subject
     */
    public function destroy(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $specializationId = (int) $subject->specialization_id;
        // Bài học là con của Môn - xoá Môn phải xoá theo (cùng la soft-delete, khôi phục được ở Thùng rác).
        $subject->lessons()->delete();
        $subject->delete();
        app(LmsCourseProvisioningService::class)->provisionForSpecialization($specializationId, request()->user());

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Môn học và toàn bộ bài học đã được xóa thành công!');
    }

    /**
     * Xóa nhiều môn học cùng lúc (chọn nhiều qua checkbox) - mỗi môn vẫn
     * kéo theo xóa toàn bộ bài học của nó, giống destroy() từng cái.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một môn học để xóa.');
        }

        $subjects = Subject::whereIn('id', $ids)->get();
        $specializationIds = [];
        $deleted = 0;

        foreach ($subjects as $subject) {
            TrainingScheduleAccess::ensureSubjectInScope($subject);
            $specializationIds[(int) $subject->specialization_id] = true;
            $subject->lessons()->delete();
            $subject->delete();
            $deleted++;
        }

        foreach (array_keys($specializationIds) as $specializationId) {
            app(LmsCourseProvisioningService::class)->provisionForSpecialization($specializationId, $request->user());
        }

        return redirect()
            ->route('subjects.index')
            ->with('success', "Đã xóa {$deleted} môn học cùng toàn bộ bài học.");
    }

    /**
     * Restore the specified subject
     */
    public function restore($id)
    {
        // Permission already checked by middleware
        $subject = Subject::withTrashed()->findOrFail($id);
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $subject->restore();
        app(LmsCourseProvisioningService::class)->provisionForSpecialization(
            (int) $subject->specialization_id,
            request()->user()
        );

        return redirect()
            ->route('subjects.show', $subject->id)
            ->with('success', 'Môn học đã được khôi phục thành công!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $subject->update([
            'is_active' => ! $subject->is_active,
            'updated_by' => Auth::id(),
        ]);
        app(LmsCourseProvisioningService::class)->provisionForSpecialization(
            (int) $subject->specialization_id,
            request()->user()
        );

        $status = $subject->is_active ? 'kích hoạt' : 'tạm dừng';

        return redirect()
            ->back()
            ->with('success', "Môn học đã được {$status} thành công!");
    }

    /**
     * Toggle required status
     */
    public function toggleRequired(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $subject->update([
            'is_required' => ! $subject->is_required,
            'updated_by' => Auth::id(),
        ]);

        $type = $subject->is_required ? 'bắt buộc' : 'tự chọn';

        return redirect()
            ->back()
            ->with('success', "Môn học đã được chuyển thành {$type} thành công!");
    }

    /**
     * Generate unique code from name
     */
    private function generateUniqueCode($name)
    {
        $baseCode = Str::upper(Str::slug($name, ''));
        $baseCode = substr($baseCode, 0, 10); // Limit to 10 characters

        $code = $baseCode;
        $counter = 1;

        while (Subject::where('code', $code)->exists()) {
            $code = $baseCode.$counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Export subjects to CSV
     */
    public function export(Request $request)
    {
        // Permission already checked by middleware
        $query = Subject::with(['specialization', 'creator', 'updater']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('specialization_id')) {
            $query->bySpecialization($request->specialization_id);
        }
        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }
        if ($request->filled('assessment_method')) {
            $query->byAssessmentMethod($request->assessment_method);
        }
        if ($request->filled('subject_type')) {
            if ($request->subject_type === 'required') {
                $query->required();
            } elseif ($request->subject_type === 'elective') {
                $query->elective();
            }
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        TrainingDept::applySubjectFacultyScope($query);

        $subjects = $query->get();

        $filename = 'subjects_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($subjects) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Mã',
                'Tên môn học',
                'Lớp',
                'Số tín chỉ',
                'Số Tiết lý thuyết',
                'Số Tiết thực hành',
                'Số Tiết tự học',
                'Số Tiết thi/kiểm tra',
                'Tổng tiết',
                'Cấp độ',
                'Phương pháp đánh giá',
                'Tính chất',
                'Trạng thái',
                'Người tạo',
                'Ngày tạo',
                'Cập nhật cuối',
            ]);

            // CSV data
            foreach ($subjects as $subject) {
                fputcsv($file, [
                    $subject->code,
                    $subject->name,
                    $subject->specialization->name ?? 'N/A',
                    $subject->credits,
                    $subject->theory_hours,
                    $subject->practice_hours,
                    $subject->self_study_hours,
                    $subject->exam_hours,
                    $subject->total_hours,
                    $subject->level_text,
                    $subject->assessment_method_text,
                    $subject->subject_type_text,
                    $subject->status_text,
                    $subject->creator->name ?? 'N/A',
                    $subject->created_at->format('d/m/Y H:i'),
                    $subject->updated_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get subjects by specialization for AJAX
     */
    public function getBySpecialization(Request $request)
    {
        $specializationId = $request->get('specialization_id');
        $query = $request->get('q');

        $subjectsQuery = Subject::active()
            ->when($specializationId, function ($q) use ($specializationId) {
                $q->bySpecialization($specializationId);
            })
            ->when($query, function ($q) use ($query) {
                $q->search($query);
            })
            ->select('id', 'name', 'code', 'credits', 'is_special_activity')
            ->limit(20);
        TrainingDept::applySubjectFacultyScope($subjectsQuery);
        $subjects = $subjectsQuery->get();

        return response()->json([
            'results' => $subjects->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->name.' ('.$item->code.') - '.$item->credits.' TC',
                    'data' => $item,
                ];
            }),
        ]);
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $fileName = 'template_import_subjects_'.date('Y-m-d').'.xlsx';

        return Excel::download(new SubjectsTemplateExport, $fileName);
    }

    /**
     * Import subjects from Excel file (create page).
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'specialization_id' => ['required', 'exists:specializations,id'],
            'code_prefix' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'specialization_id.required' => 'Vui lòng chọn ngành đào tạo trước khi import.',
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'Chỉ chấp nhận file Excel (.xlsx, .xls).',
            'code_prefix.regex' => 'Tiền tố mã môn chỉ gồm chữ, số và gạch dưới.',
        ]);

        $specialization = Specialization::findOrFail($request->integer('specialization_id'));
        $prefix = SubjectCodePrefix::normalizePrefix(
            $request->input('code_prefix'),
            $specialization
        );

        try {
            $import = new SubjectsImport($specialization->id, $prefix);
            Excel::import($import, $request->file('file'));

            app(LmsCourseProvisioningService::class)->provisionForSpecialization(
                (int) $specialization->id,
                $request->user()
            );

            $failures = $import->failures();
            $message = "Import xong (mã ngành {$prefix}): "
                ."thêm mới {$import->getImportedCount()}, "
                ."cập nhật {$import->getUpdatedCount()}, "
                ."bỏ qua {$import->getSkippedCount()} dòng trống.";

            if ($failures->isNotEmpty()) {
                $failMessages = $failures->take(10)->map(function ($failure) {
                    return 'Dòng '.$failure->row().': '.implode('; ', $failure->errors());
                })->implode(' | ');

                return redirect()
                    ->route('subjects.create')
                    ->with('warning', $message.' Một số dòng lỗi: '.$failMessages);
            }

            return redirect()
                ->route('subjects.index', ['specialization_id' => $specialization->id])
                ->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()
                ->route('subjects.create')
                ->with('error', 'Không import được file Excel: '.$e->getMessage());
        }
    }

    /**
     * Import subjects from parsed JSON rows (Specialization show modal).
     */
    public function import(Request $request, Specialization $specialization)
    {
        $request->validate([
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:255',
            'subjects.*.abbreviation' => 'nullable|string|max:50',
            'subjects.*.credits' => 'nullable|integer|min:1|max:30',
            'subjects.*.theory_hours' => 'nullable|integer|min:0',
            'subjects.*.practice_hours' => 'nullable|integer|min:0',
            'subjects.*.self_study_hours' => 'nullable|integer|min:0',
            'subjects.*.exam_hours' => 'nullable|integer|min:0',
            'code_prefix' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        $prefix = SubjectCodePrefix::normalizePrefix(
            $request->input('code_prefix'),
            $specialization
        );

        $subjects = $request->input('subjects');
        $importedCount = 0;
        $updatedCount = 0;
        $errors = [];

        try {
            \DB::beginTransaction();

            foreach ($subjects as $index => $subjectData) {
                $rowNumber = $index + 2;

                try {
                    $localCode = ! empty($subjectData['code'])
                        ? (string) $subjectData['code']
                        : $this->generateUniqueCode($subjectData['name']);
                    $code = SubjectCodePrefix::buildSubjectCode($prefix, $localCode);

                    $level = $this->mapLevel($subjectData['level'] ?? 'Cơ bản');
                    $semester = $this->mapSemester($subjectData['semester'] ?? '');
                    $assessmentMethod = $this->mapAssessmentMethod($subjectData['assessment_method'] ?? 'Thi viết');

                    $existingSubject = Subject::where('code', $code)->first();

                    $abbreviation = isset($subjectData['abbreviation'])
                        ? trim((string) $subjectData['abbreviation'])
                        : '';
                    if ($abbreviation === '') {
                        $abbreviation = Subject::generateAbbreviationFromName((string) $subjectData['name']);
                    }
                    $abbreviation = $abbreviation !== '' ? mb_strtoupper($abbreviation, 'UTF-8') : null;

                    $payload = [
                        'name' => $subjectData['name'],
                        'abbreviation' => $abbreviation,
                        'description' => $subjectData['description'] ?? null,
                        'specialization_id' => $specialization->id,
                        'credits' => (int) ($subjectData['credits'] ?? 1),
                        'theory_hours' => (int) ($subjectData['theory_hours'] ?? 0),
                        'practice_hours' => (int) ($subjectData['practice_hours'] ?? 0),
                        'self_study_hours' => (int) ($subjectData['self_study_hours'] ?? 0),
                        'exam_hours' => (int) ($subjectData['exam_hours'] ?? 0),
                        'level' => $level,
                        'semester' => $semester,
                        'assessment_method' => $assessmentMethod,
                        'updated_by' => Auth::id(),
                    ];

                    if ($existingSubject) {
                        $existingSubject->update($payload);
                        $updatedCount++;
                    } else {
                        Subject::create(array_merge($payload, [
                            'code' => $code,
                            'is_required' => true,
                            'is_active' => true,
                            'created_by' => Auth::id(),
                        ]));
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            \DB::commit();

            app(LmsCourseProvisioningService::class)->provisionForSpecialization(
                (int) $specialization->id,
                $request->user()
            );

            $message = "Đã nhập thành công {$importedCount} môn học mới (mã ngành {$prefix})";
            if ($updatedCount > 0) {
                $message .= ", cập nhật {$updatedCount} môn học đã tồn tại";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported' => $importedCount,
                    'updated' => $updatedCount,
                    'errors' => $errors,
                    'code_prefix' => $prefix,
                ],
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: '.$e->getMessage(),
                'errors' => $errors,
            ], 500);
        }
    }

    /**
     * Map level from Vietnamese or English to database value
     */
    protected function mapLevel($level): string
    {
        $level = strtolower(trim($level));

        $levelMap = [
            'cơ bản' => 'basic',
            'co ban' => 'basic',
            'basic' => 'basic',
            'trung cấp' => 'intermediate',
            'trung cap' => 'intermediate',
            'intermediate' => 'intermediate',
            'nâng cao' => 'advanced',
            'nang cao' => 'advanced',
            'advanced' => 'advanced',
        ];

        return $levelMap[$level] ?? 'basic';
    }

    /**
     * Map semester from various formats
     */
    protected function mapSemester($semester): ?string
    {
        if (empty($semester)) {
            return null;
        }

        $semester = strtolower(trim($semester));

        $semesterMap = [
            'học kỳ 1' => 'semester_1',
            'hoc ky 1' => 'semester_1',
            'semester 1' => 'semester_1',
            'semester_1' => 'semester_1',
            '1' => 'semester_1',
            'học kỳ 2' => 'semester_2',
            'hoc ky 2' => 'semester_2',
            'semester 2' => 'semester_2',
            'semester_2' => 'semester_2',
            '2' => 'semester_2',
            'học kỳ 3' => 'semester_3',
            'hoc ky 3' => 'semester_3',
            'semester 3' => 'semester_3',
            'semester_3' => 'semester_3',
            '3' => 'semester_3',
            'học kỳ 4' => 'semester_4',
            'hoc ky 4' => 'semester_4',
            'semester 4' => 'semester_4',
            'semester_4' => 'semester_4',
            '4' => 'semester_4',
            'học kỳ 5' => 'semester_5',
            'hoc ky 5' => 'semester_5',
            'semester 5' => 'semester_5',
            'semester_5' => 'semester_5',
            '5' => 'semester_5',
            'học kỳ 6' => 'semester_6',
            'hoc ky 6' => 'semester_6',
            'semester 6' => 'semester_6',
            'semester_6' => 'semester_6',
            '6' => 'semester_6',
            'học kỳ hè' => 'summer',
            'hoc ky he' => 'summer',
            'summer' => 'summer',
            'hè' => 'summer',
            'he' => 'summer',
        ];

        return $semesterMap[$semester] ?? null;
    }

    /**
     * Map assessment method from Vietnamese or English
     */
    protected function mapAssessmentMethod($method): string
    {
        $method = strtolower(trim($method));

        $methodMap = [
            'thi viết' => 'exam',
            'thi viet' => 'exam',
            'exam' => 'exam',
            'thi trắc nghiệm' => 'multiple_choice_questions',
            'thi trac nghiem' => 'multiple_choice_questions',
            'trắc nghiệm' => 'multiple_choice_questions',
            'trac nghiem' => 'multiple_choice_questions',
            'multiple choice' => 'multiple_choice_questions',
            'multiple_choice_questions' => 'multiple_choice_questions',
            'bài tập' => 'assignment',
            'bai tap' => 'assignment',
            'assignment' => 'assignment',
            'đồ án' => 'project',
            'do an' => 'project',
            'project' => 'project',
            'tổng hợp' => 'combined',
            'tong hop' => 'combined',
            'combined' => 'combined',
        ];

        return $methodMap[$method] ?? 'exam';
    }

    /**
     * Show form to assign instructors to subject
     */
    public function assignInstructors(Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $subject->load(['specialization', 'instructors']);

        // Get all active instructors with relationships
        $instructorsQuery = Instructor::with(['unit', 'specialization'])
            ->where('status', 'active')
            ->orderBy('name');
        ManagerUnitScope::applyToInstructorQuery($instructorsQuery);
        $instructors = $instructorsQuery->get();

        // Get filter options
        $units = Unit::active()->pluck('name', 'id');
        $specializations = Specialization::active()->with('trainingSystem:id,name')->orderBy('name')->get()
            ->mapWithKeys(fn ($item) => [$item->id => $item->selection_label]);

        return view('subject::assign-instructors', compact('subject', 'instructors', 'units', 'specializations'));
    }

    /**
     * Store instructor assignments for subject
     */
    public function storeInstructorAssignments(AssignInstructorsRequest $request, Subject $subject)
    {
        TrainingScheduleAccess::ensureSubjectInScope($subject);
        $instructorIds = $request->validated()['instructor_ids'];
        foreach ($instructorIds as $instructorId) {
            TrainingScheduleAccess::ensureInstructorInScope((int) $instructorId);
        }

        if (TrainingDept::isFacultyManager()) {
            $managedUnitIds = ManagerUnitScope::managedUnitIds();
            $preservedIds = $subject->instructors()
                ->whereNotIn('instructors.unit_id', $managedUnitIds)
                ->pluck('instructors.id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $subject->instructors()->sync(array_values(array_unique(array_merge(
                $preservedIds,
                array_map('intval', $instructorIds)
            ))));
        } else {
            $subject->instructors()->sync($instructorIds);
        }

        app(LmsCourseProvisioningService::class)->syncTeachingAssignmentsForSubjects([$subject->id]);

        $count = count($instructorIds);

        return redirect()
            ->route('subjects.show', $subject)
            ->with('success', "Đã phân công thành công {$count} giảng viên cho môn học {$subject->name}!");
    }
}
