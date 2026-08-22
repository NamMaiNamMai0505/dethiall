<?php

namespace Modules\TeachingAssignment\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\ManagerUnitScope;
use App\Support\TrainingDept;
use App\Support\TrainingScheduleAccess;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\TeachingAssignment\Requests\AssignSubjectsRequest;
use Modules\Unit\Models\Unit;

class TeachingAssignmentController extends ModuleBaseController
{
    public function index()
    {
        $query = Instructor::with([
            'subjects' => fn ($subjectQuery) => TrainingDept::applySubjectFacultyScope($subjectQuery),
            'unit',
            'specialization',
        ]);

        // Search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Unit filter
        if (request('unit_id')) {
            $query->where('unit_id', request('unit_id'));
        }

        ManagerUnitScope::applyToInstructorQuery($query);

        // Status filter
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Sorting
        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $unitsQuery = Unit::where('level', 2);
        if (ManagerUnitScope::isScoped()) {
            $unitsQuery->whereIn('id', ManagerUnitScope::managedUnitIds());
        }
        $units = $unitsQuery->get();
        $perPage = request('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10; // Default to 10 if invalid
        }
        $instructors = $query->paginate($perPage)->appends(request()->query());

        return view('teaching-assignment::index', compact('instructors', 'units'));
    }

    public function show(Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        $instructor->load([
            'subjects' => fn ($subjectQuery) => TrainingDept::applySubjectFacultyScope($subjectQuery),
            'unit',
            'specialization',
        ]);

        return view('teaching-assignment::show', compact('instructor'));
    }

    public function edit(Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        $instructor->load([
            'subjects' => fn ($subjectQuery) => TrainingDept::applySubjectFacultyScope($subjectQuery),
        ]);
        $specializations = Specialization::query()
            ->with('trainingSystem:id,name')
            ->with(['subjects' => function ($q) {
                TrainingDept::applySubjectFacultyScope($q);
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('teaching-assignment::edit', compact('instructor', 'specializations'));
    }

    public function update(AssignSubjectsRequest $request, Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        $previousSubjectIds = $instructor->subjects()->pluck('subjects.id')->all();
        $subjectIds = $request->subject_ids ?? [];
        foreach ($subjectIds as $subjectId) {
            TrainingScheduleAccess::ensureSubjectInScope((int) $subjectId);
        }
        $this->syncSubjectsWithinScope($instructor, $subjectIds);
        app(LmsCourseProvisioningService::class)->syncTeachingAssignmentsForSubjects(
            array_merge($previousSubjectIds, $subjectIds)
        );

        return redirect()
            ->route('teaching-assignments.show', $instructor)
            ->with('success', 'Cập nhật phân công giảng viên thành công.');
    }

    public function create()
    {
        // Tất cả ngành + môn (mặc định hiện đủ môn; lọc ngành/học kỳ trên UI)
        $specializations = Specialization::query()
            ->with('trainingSystem:id,name')
            ->with(['subjects' => function ($q) {
                TrainingDept::applySubjectFacultyScope($q);
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $instructor = null;

        // Nếu có instructor_id trong query string (mode edit)
        if (request()->has('instructor_id') && request('instructor_id')) {
            $instructor = Instructor::with(['subjects', 'unit'])
                ->findOrFail(request('instructor_id'));
            ManagerUnitScope::ensureCanAccessInstructor($instructor);
        }

        return view('teaching-assignment::create', compact('specializations', 'instructor'));
    }

    public function store(AssignSubjectsRequest $request)
    {
        $instructor = Instructor::findOrFail($request->instructor_id);
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        $subjectIds = $request->subject_ids ?? [];
        foreach ($subjectIds as $subjectId) {
            TrainingScheduleAccess::ensureSubjectInScope((int) $subjectId);
        }

        try {
            $previousSubjectIds = $instructor->subjects()->pluck('subjects.id')->all();
            $this->syncSubjectsWithinScope($instructor, $subjectIds);
            app(LmsCourseProvisioningService::class)->syncTeachingAssignmentsForSubjects(
                array_merge($previousSubjectIds, $subjectIds)
            );

            return redirect()
                ->route('teaching-assignments.show', $instructor)
                ->with('success', 'Phân công giảng dạy cho giảng viên "'.$instructor->name.'" đã được lưu thành công. Đã phân công '.count($subjectIds).' môn học.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi lưu phân công: '.$e->getMessage());
        }
    }

    public function destroy(Instructor $instructor)
    {
        ManagerUnitScope::ensureCanAccessInstructor($instructor);
        $subjectIds = $instructor->subjects()->pluck('subjects.id')->all();
        if (TrainingDept::isFacultyManager()) {
            $instructor->subjects()->detach($this->facultySubjectIds());
        } else {
            $instructor->subjects()->detach();
        }
        app(LmsCourseProvisioningService::class)->syncTeachingAssignmentsForSubjects($subjectIds);

        return back()->with(
            'success',
            TrainingDept::isFacultyManager()
                ? 'Đã xoá các phân công môn thuộc khoa của giảng viên này.'
                : 'Đã xoá toàn bộ phân công của giảng viên này.'
        );
    }

    /** @param list<int|string> $subjectIds */
    private function syncSubjectsWithinScope(Instructor $instructor, array $subjectIds): void
    {
        $subjectIds = array_values(array_unique(array_map('intval', $subjectIds)));
        if (! TrainingDept::isFacultyManager()) {
            $instructor->subjects()->sync($subjectIds);

            return;
        }

        $ownedIds = $this->facultySubjectIds();
        $preservedIds = $instructor->subjects()
            ->whereNotIn('subjects.id', $ownedIds)
            ->pluck('subjects.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $instructor->subjects()->sync(array_values(array_unique(array_merge($preservedIds, $subjectIds))));
    }

    /** @return list<int> */
    private function facultySubjectIds(): array
    {
        $query = Subject::query();
        TrainingDept::applySubjectFacultyScope($query);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
