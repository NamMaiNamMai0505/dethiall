<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ManagerUnitScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\StandardHours\Exports\StandardHoursStatisticsExport;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Requests\RetrieveTeachingScheduleRequest;
use Modules\StandardHours\Requests\StoreYearlyDeclarationRequest;
use Modules\StandardHours\Services\AnnualDeclarationService;
use Modules\StandardHours\Services\MyResultService;
use Modules\StandardHours\Support\InstructorScope;

class MyResultController extends StandardHoursBaseController
{
    protected bool $allowInstructorAccess = true;

    public function __construct(
        private readonly MyResultService $myResultService,
        private readonly AnnualDeclarationService $annualDeclarationService,
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $instructorId = $this->resolveInstructorId($request);
        $isManagementView = $instructorId === null || ! auth()->user()?->isInstructor();
        $instructors = $isManagementView
            ? InstructorScope::instructorsQuery()
                ->get()
                ->mapWithKeys(fn ($instructor) => [
                    $instructor->id => $instructor->name.' ('.$instructor->code.')'
                        .($instructor->unit?->name ? ' — '.$instructor->unit->name : ''),
                ])
                ->all()
            : [];
        $years = $this->myResultService->getYears($instructorId);
        $defaultYear = $request->get('year') ?? array_key_first($years);
        $filters = $request->all();
        if ($defaultYear !== null) {
            $filters['year'] = $defaultYear;
        }
        $results = $this->myResultService->paginate($instructorId, $filters);
        $selectedResult = $instructorId
            ? $results->getCollection()->first()
            : null;
        $selectedInstructorId = $instructorId;

        return view('standardhours::my-results.index', compact(
            'results',
            'years',
            'defaultYear',
            'selectedResult',
            'isManagementView',
            'instructors',
            'selectedInstructorId',
        ));
    }

    public function show(YearlyResult $yearlyResult)
    {
        InstructorScope::ensureOwnsRecord($yearlyResult->instructor_id);
        $yearlyResult->load(['instructor.unit', 'objectType', 'position', 'calculator', 'declarer', 'locker']);

        return view('standardhours::my-results.show', compact('yearlyResult'));
    }

    public function declaration(Request $request)
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
        ]);
        $instructorId = $this->resolveInstructorId($request, true);
        $formData = $this->annualDeclarationService->getFormData(
            $instructorId,
            $request->integer('year') ?: null
        );

        return view('standardhours::my-results.declaration', $formData);
    }

    public function retrieveSchedule(RetrieveTeachingScheduleRequest $request)
    {
        $data = $request->validated();
        $instructorId = $this->resolveInstructorId($request, true);

        try {
            $result = $this->annualDeclarationService->retrieveSchedule(
                $instructorId,
                $data['from_date'],
                $data['to_date'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function storeDeclaration(StoreYearlyDeclarationRequest $request)
    {
        $instructorId = $this->resolveInstructorId($request, true);

        try {
            $result = $this->annualDeclarationService->save($instructorId, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.my-results.show', $result)
            ->with(
                'success',
                'Đã gửi bảng kê khai giờ chuẩn. Giờ giảng dạy khác sẽ được cộng sau khi cấp quản lý duyệt.'
            );
    }

    public function export(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $instructorId = $this->resolveInstructorId($request);

        $filters = [
            'year' => $request->input('year'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];
        if ($instructorId !== null) {
            $filters['instructor_id'] = $instructorId;
        }

        $results = $this->myResultService->getForExport($instructorId, $filters);

        if ($results->isEmpty()) {
            return back()->with('error', 'Chưa có kết quả tính giờ cho năm đã chọn.');
        }

        $year = $filters['year'];
        $instructor = $instructorId ? $results->first()->instructor : null;
        $scopeName = $instructor ? str($instructor->code)->slug() : 'pham-vi-quan-ly';
        $filename = 'thong-ke-gio-chuan-'.$scopeName.'-'.$year.'-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new StandardHoursStatisticsExport(
                $results,
                $year,
                $request->input('from_date'),
                $request->input('to_date'),
            ),
            $filename
        );
    }

    private function resolveInstructorId(Request $request, bool $required = false): ?int
    {
        $personalInstructorId = InstructorScope::instructorId();
        if ($personalInstructorId !== null) {
            return $personalInstructorId;
        }

        abort_unless(
            auth()->user()?->isManagementActor(),
            403,
            'Chức năng này chỉ dành cho giảng viên hoặc tài khoản quản lý.'
        );

        $instructorId = $request->integer('instructor_id');
        if ($instructorId <= 0) {
            if ($required) {
                abort(422, 'Vui lòng chọn giảng viên cần kê khai.');
            }

            return null;
        }

        ManagerUnitScope::ensureCanAccessInstructor($instructorId);

        return $instructorId;
    }
}
