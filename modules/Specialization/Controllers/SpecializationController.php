<?php

namespace Modules\Specialization\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Specialization\Models\Specialization;
use Modules\Specialization\Models\TrainingSystem;
use Modules\Specialization\Requests\CreateSpecializationRequest;
use Modules\Specialization\Requests\UpdateSpecializationRequest;
use Modules\Subject\Models\SubjectLesson;

class SpecializationController extends ModuleBaseController
{
    /**
     * Display a listing of specializations
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = Specialization::with(['creator', 'updater', 'trainingSystem'])
            ->withCount('subjects');

        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by Hệ đào tạo
        if ($request->filled('training_system_id')) {
            $query->where('training_system_id', $request->integer('training_system_id'));
        }

        // Apply level filter
        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('training_form')) {
            $query->where('training_form', $request->string('training_form')->toString());
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortBy = in_array($sortBy, ['code', 'major_code', 'name', 'level', 'training_form', 'duration_months', 'created_at'], true)
            ? $sortBy
            : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Get per page value (default 10, allow 5, 10, 15, 25, 50)
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $specializations = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $levels = Specialization::getLevelOptions();
        $certificationTypes = Specialization::getCertificationTypeOptions();
        $trainingForms = Specialization::getTrainingFormOptions();
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');

        return view('specialization::index', compact('specializations', 'levels', 'certificationTypes', 'trainingForms', 'trainingSystems'));
    }

    /**
     * Show the form for creating a new specialization
     */
    public function create()
    {
        // Permission already checked by middleware

        $levels = Specialization::getLevelOptions();
        $certificationTypes = Specialization::getCertificationTypeOptions();
        $trainingForms = Specialization::getTrainingFormOptions();
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');

        return view('specialization::create', compact('levels', 'certificationTypes', 'trainingForms', 'trainingSystems'));
    }

    /**
     * Store a newly created specialization
     */
    public function store(CreateSpecializationRequest $request)
    {
        // Permission already checked by middleware
        $data = $request->validated();

        // Generate unique code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $specialization = Specialization::create($data);

        return redirect()
            ->route('specializations.show', $specialization->id)
            ->with('success', 'Ngành đào tạo đã được tạo thành công!');
    }

    /**
     * Display the specified specialization
     */
    public function show(Specialization $specialization)
    {
        // Permission already checked by middleware

        $specialization->load(['creator', 'updater', 'trainingSystem'])->loadCount('subjects');

        return view('specialization::show', compact('specialization'));
    }

    /**
     * Show the form for editing the specified specialization
     */
    public function edit(Specialization $specialization)
    {
        // Permission already checked by middleware

        $levels = Specialization::getLevelOptions();
        $certificationTypes = Specialization::getCertificationTypeOptions();
        $trainingForms = Specialization::getTrainingFormOptions();
        $trainingSystems = TrainingSystem::query()->active()->orderBy('sort_order')->pluck('name', 'id');

        return view('specialization::edit', compact('specialization', 'levels', 'certificationTypes', 'trainingForms', 'trainingSystems'));
    }

    /**
     * Update the specified specialization
     */
    public function update(UpdateSpecializationRequest $request, Specialization $specialization)
    {
        // Permission already checked by middleware
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = $specialization->code ?: $this->generateUniqueCode();
        }
        $data['updated_by'] = Auth::id();

        $specialization->update($data);

        return redirect()
            ->route('specializations.show', $specialization->id)
            ->with('success', 'Ngành đào tạo đã được cập nhật thành công!');
    }

    /**
     * Remove the specified specialization
     */
    public function destroy(Specialization $specialization)
    {
        // Permission already checked by middleware
        // Ngành là Cha của Môn, Môn là Cha của Bài - xoá Ngành phải xoá theo
        // toàn bộ Môn và Bài thuộc ngành (cùng là soft-delete, khôi phục
        // được ở Thùng rác).
        DB::transaction(function () use ($specialization) {
            $subjectIds = $specialization->subjects()->pluck('id');
            if ($subjectIds->isNotEmpty()) {
                SubjectLesson::whereIn('subject_id', $subjectIds)->delete();
                $specialization->subjects()->delete();
            }
            $specialization->delete();
        });

        return redirect()
            ->route('specializations.index')
            ->with('success', 'Ngành đào tạo cùng toàn bộ môn học, bài học đã được xóa thành công!');
    }

    /**
     * Restore the specified specialization
     */
    public function restore($id)
    {
        // Permission already checked by middleware
        $specialization = Specialization::withTrashed()->findOrFail($id);
        $specialization->restore();

        return redirect()
            ->route('specializations.show', $specialization->id)
            ->with('success', 'Ngành đào tạo đã được khôi phục thành công!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Specialization $specialization)
    {
        // Permission already checked by middleware
        $specialization->update([
            'is_active' => ! $specialization->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $specialization->is_active ? 'kích hoạt' : 'tạm dừng';

        return redirect()
            ->back()
            ->with('success', "Ngành đào tạo đã được {$status} thành công!");
    }

    /**
     * Generate unique code from name
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = 'CTDT-'.Str::upper(Str::random(8));
        } while (Specialization::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Export specializations to CSV
     */
    public function export(Request $request)
    {
        // Permission already checked by middleware
        $query = Specialization::with(['creator', 'updater', 'trainingSystem']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }
        if ($request->filled('training_system_id')) {
            $query->where('training_system_id', $request->integer('training_system_id'));
        }
        if ($request->filled('training_form')) {
            $query->where('training_form', $request->string('training_form')->toString());
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $specializations = $query->get();

        $filename = 'specializations_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($specializations) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Mã ngành',
                'Tên',
                'Hệ đào tạo',
                'Hình thức đào tạo',
                'Mô tả',
                'Cấp độ',
                'Thời gian (tháng)',
                'Loại chứng chỉ',
                'Trạng thái',
                'Người tạo',
                'Ngày tạo',
                'Cập nhật cuối',
            ]);

            // CSV data
            foreach ($specializations as $specialization) {
                fputcsv($file, [
                    $specialization->major_code,
                    $specialization->name,
                    $specialization->trainingSystem?->name ?? 'Chưa xác định',
                    $specialization->training_form_text,
                    $specialization->description,
                    $specialization->level_text,
                    $specialization->duration_months,
                    $specialization->certification_type_text,
                    $specialization->status_text,
                    $specialization->creator->name ?? 'N/A',
                    $specialization->created_at->format('d/m/Y H:i'),
                    $specialization->updated_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
