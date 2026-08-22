<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use App\Support\ApprovalAgency;
use Illuminate\Http\Request;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Requests\ReviewYearlyDeclarationRequest;
use Modules\StandardHours\Requests\RunCalculationRequest;
use Modules\StandardHours\Services\AnnualDeclarationService;
use Modules\StandardHours\Services\CalculationService;
use Modules\StandardHours\Support\InstructorScope;

class CalculationController extends StandardHoursBaseController
{
    public function __construct(
        private readonly CalculationService $calculationService,
        private readonly AnnualDeclarationService $annualDeclarationService,
    ) {
        parent::__construct();

        // Chạy tính giờ chuẩn nằm ở cột "Thêm" của ứng dụng Tính giờ chuẩn;
        // duyệt hồ sơ kê khai nằm ở cột "Duyệt". Không nhận quyền tổng phân hệ.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_CREATE))
            ->only(['preview', 'calculate', 'rollback', 'lock']);
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_APPROVE))
            ->only(['reviewDeclaration']);
    }

    public function index(Request $request)
    {
        $filterOptions = $this->calculationService->getFilterOptions();
        $year = $request->get('year') ?? array_key_first($filterOptions['years']);
        $filters = $request->all();
        $filters['year'] = $year;
        $results = $this->calculationService->paginateResults($filters);
        $yearSummary = $this->calculationService->getYearSummary($year);
        $history = $this->calculationService->getHistory();
        $previewData = session('preview_data');

        return view('standardhours::calculations.index', array_merge(
            compact('results', 'yearSummary', 'history', 'previewData', 'year'),
            $filterOptions
        ));
    }

    public function show(YearlyResult $yearlyResult)
    {
        InstructorScope::ensureOwnsRecord($yearlyResult->instructor_id);
        $yearlyResult->load(['instructor.unit', 'objectType', 'position', 'calculator', 'declarer', 'locker']);

        return view('standardhours::calculations.show', compact('yearlyResult'));
    }

    public function preview(RunCalculationRequest $request)
    {
        try {
            $previewData = $this->calculationService->preview(
                $request->validated('year'),
                $request->validated('unit_id')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('preview_data', $previewData)
            ->with('success', "Xem trước: {$previewData['processed']} GV, bỏ qua {$previewData['skipped']} GV.");
    }

    public function calculate(RunCalculationRequest $request)
    {
        try {
            $result = $this->calculationService->calculate(
                $request->validated('year'),
                $request->validated('unit_id')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.calculations.index', [
                'year' => $request->validated('year'),
            ])
            ->with('success', "Đã tính giờ cho {$result['processed']} giảng viên. Bỏ qua {$result['skipped']} GV.");
    }

    public function rollback(Request $request)
    {
        $request->validate(['year' => ['required', 'integer', 'min:2000', 'max:2200']]);

        try {
            $deleted = $this->calculationService->rollback($request->input('year'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã hoàn tác {$deleted} kết quả tính giờ.");
    }

    public function lock(Request $request)
    {
        $request->validate(['year' => ['required', 'integer', 'min:2000', 'max:2200']]);

        try {
            $locked = $this->calculationService->lock($request->input('year'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã khóa {$locked} kết quả tính giờ.");
    }

    public function reviewDeclaration(
        ReviewYearlyDeclarationRequest $request,
        YearlyResult $yearlyResult
    ) {
        ApprovalAgency::ensureCanReviewAnnualStandardHours(auth()->user());

        try {
            $result = $this->annualDeclarationService->review(
                $yearlyResult,
                $request->validated('declaration_status'),
                $request->validated('declaration_review_note')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            $result->declaration_status === YearlyResult::DECLARATION_APPROVED
                ? 'Đã duyệt kê khai và tính lại giờ chuẩn.'
                : 'Đã từ chối kê khai; giờ giảng khác không được cộng vào kết quả.'
        );
    }
}
