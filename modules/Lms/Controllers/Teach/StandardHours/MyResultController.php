<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Exports\StandardHoursStatisticsExport;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Requests\RetrieveTeachingScheduleRequest;
use Modules\StandardHours\Requests\StoreYearlyDeclarationRequest;
use Modules\StandardHours\Services\AnnualDeclarationService;
use Modules\StandardHours\Services\MyResultService;
use Modules\StandardHours\Support\InstructorScope;

/**
 * Kê khai giờ chuẩn — bản sao native trong shell LMS, chỉ tự phục vụ GV
 * (không có view quản lý/duyệt). Tái sử dụng các Service của module
 * StandardHours qua DI — không sao chép business logic.
 */
class MyResultController extends Controller
{
    public function __construct(
        private readonly MyResultService $myResultService,
        private readonly AnnualDeclarationService $annualDeclarationService,
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $instructorId = $this->resolveInstructorId();
        $years = $this->myResultService->getYears($instructorId);
        $defaultYear = $request->get('year') ?? array_key_first($years);
        $filters = $request->all();
        if ($defaultYear !== null) {
            $filters['year'] = $defaultYear;
        }
        $results = $this->myResultService->paginate($instructorId, $filters);
        $selectedResult = $results->getCollection()->first();

        return view('lms::teach.standard-hours.my-results.index', compact(
            'results',
            'years',
            'defaultYear',
            'selectedResult',
        ));
    }

    public function show(YearlyResult $yearlyResult)
    {
        InstructorScope::ensureOwnsRecord($yearlyResult->instructor_id);
        $yearlyResult->load(['instructor.unit', 'objectType', 'position', 'calculator', 'declarer', 'locker']);

        return view('lms::teach.standard-hours.my-results.show', compact('yearlyResult'));
    }

    public function declaration(Request $request)
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
        ]);
        $instructorId = $this->resolveInstructorId();
        $formData = $this->annualDeclarationService->getFormData(
            $instructorId,
            $request->integer('year') ?: null
        );

        return view('lms::teach.standard-hours.my-results.declaration', $formData);
    }

    public function retrieveSchedule(RetrieveTeachingScheduleRequest $request)
    {
        $data = $request->validated();
        $instructorId = $this->resolveInstructorId();

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
        $instructorId = $this->resolveInstructorId();

        try {
            $result = $this->annualDeclarationService->save($instructorId, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lms.teach.standard-hours.my-results.show', $result)
            ->with(
                'success',
                'Đã gửi bảng kê khai giờ chuẩn. Giờ giảng dạy khác sẽ được cộng sau khi cấp quản lý duyệt.'
            );
    }

    public function export(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $instructorId = $this->resolveInstructorId();

        $filters = [
            'year' => $request->input('year'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'instructor_id' => $instructorId,
        ];

        $results = $this->myResultService->getForExport($instructorId, $filters);

        if ($results->isEmpty()) {
            return back()->with('error', 'Chưa có kết quả tính giờ cho năm đã chọn.');
        }

        $year = $filters['year'];
        $instructor = $results->first()->instructor;
        $scopeName = $instructor ? str($instructor->code)->slug() : 'gio-chuan';
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

    private function resolveInstructorId(): int
    {
        return InstructorScope::ensureInstructorUser();
    }
}
