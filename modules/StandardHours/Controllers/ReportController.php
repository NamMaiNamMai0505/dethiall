<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ManagerUnitScope;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\StandardHours\Exports\ConversionActivitiesExport;
use Modules\StandardHours\Exports\ResearchHoursStatisticsExport;
use Modules\StandardHours\Exports\StandardHoursStatisticsExport;
use Modules\StandardHours\Models\YearlyResult;
use Modules\StandardHours\Services\ConversionReportService;
use Modules\StandardHours\Services\ReportService;
use Modules\StandardHours\Services\ResearchReportService;
use Modules\StandardHours\Support\ReportDocumentLayout;

class ReportController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ConversionReportService $conversionReportService,
        private readonly ResearchReportService $researchReportService,
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $reportType = $request->get('report_type', 'total');
        $filterOptions = $this->reportService->getFilterOptions();
        $defaultYear = $request->get('year') ?? array_key_first($filterOptions['years']);
        $filters = $request->all();
        $filters['year'] = $defaultYear;

        $reports = collect();
        $summary = [];
        $conversionRows = collect();
        $researchRows = collect();

        if ($reportType === 'total') {
            $reports = $this->reportService->paginate($filters);
            $summary = $this->reportService->getSummary($filters);
        } elseif ($reportType === 'conversion') {
            $conversionRows = $this->conversionReportService->getForExport($request->only([
                'from_date', 'to_date', 'unit_id', 'unit_ids', 'instructor_id',
            ]));
        } elseif ($reportType === 'research') {
            $researchRows = $this->researchReportService->getForExport($request->only([
                'from_date', 'to_date', 'unit_id', 'unit_ids', 'instructor_id',
            ]));
        }

        return view('standardhours::reports.index', array_merge(
            compact(
                'reports',
                'summary',
                'defaultYear',
                'reportType',
                'conversionRows',
                'researchRows',
            ),
            $filterOptions
        ));
    }

    public function show(YearlyResult $yearlyResult)
    {
        $yearlyResult->load(['instructor.unit', 'objectType', 'position', 'calculator', 'declarer', 'locker']);

        return view('standardhours::reports.show', compact('yearlyResult'));
    }

    public function export(Request $request)
    {
        $reportType = $request->get('report_type', 'total');

        if ($reportType === 'conversion') {
            return $this->exportConversion($request);
        }

        if ($reportType === 'research') {
            return $this->exportResearch($request);
        }

        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'export_level' => ['nullable', 'in:personal,unit,school'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
        ]);

        $exportLevel = ReportDocumentLayout::normalizeLevel($request->input('export_level'));
        $filters = $request->only(['year', 'unit_id', 'unit_ids', 'instructor_id', 'overall_result', 'search']);

        if ($exportLevel === ReportDocumentLayout::LEVEL_UNIT) {
            $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
            if ($unitIds === []) {
                return back()->with('error', 'Vui lòng chọn ít nhất một khoa khi xuất cấp khoa.');
            }
            $filters['unit_ids'] = $unitIds;
            // Cấp khoa: không lọc 1 GV — xuất toàn bộ GV thuộc khoa đã chọn
            unset($filters['instructor_id']);
        }

        if ($exportLevel === ReportDocumentLayout::LEVEL_SCHOOL) {
            // Cấp trường: tổng hợp khoa (manager chỉ trong phạm vi khoa được gán)
            unset($filters['unit_id'], $filters['unit_ids'], $filters['instructor_id']);
        }

        // Manager always re-scoped by managed unit(s)
        ManagerUnitScope::applyToFilters($filters);

        $results = $this->reportService->getForExport($filters);

        if ($results->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu báo cáo để xuất.');
        }

        $year = $filters['year'];
        $levelSlug = match ($exportLevel) {
            ReportDocumentLayout::LEVEL_UNIT => 'khoa',
            ReportDocumentLayout::LEVEL_SCHOOL => 'truong',
            default => 'canhan',
        };
        $filename = 'thong-ke-gio-chuan-'.$levelSlug.'-'.$year.'-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new StandardHoursStatisticsExport(
                $results,
                $year,
                $request->input('from_date'),
                $request->input('to_date'),
                $exportLevel,
            ),
            $filename
        );
    }

    public function exportConversion(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'export_level' => ['nullable', 'in:personal,unit,school'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
        ]);

        $exportLevel = ReportDocumentLayout::normalizeLevel($request->input('export_level'));
        $filters = $request->only(['from_date', 'to_date', 'unit_id', 'unit_ids', 'instructor_id']);

        if ($exportLevel === ReportDocumentLayout::LEVEL_UNIT) {
            $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
            if ($unitIds === []) {
                return back()->with('error', 'Vui lòng chọn ít nhất một khoa khi xuất cấp khoa.');
            }
            $filters['unit_ids'] = $unitIds;
            unset($filters['instructor_id']);
        }

        if ($exportLevel === ReportDocumentLayout::LEVEL_SCHOOL) {
            unset($filters['unit_id'], $filters['unit_ids'], $filters['instructor_id']);
        }

        ManagerUnitScope::applyToFilters($filters);

        $rows = $this->conversionReportService->getForExport($filters);

        if ($rows->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu hoạt động chuyên môn để xuất.');
        }

        $levelSlug = match ($exportLevel) {
            ReportDocumentLayout::LEVEL_UNIT => 'khoa',
            ReportDocumentLayout::LEVEL_SCHOOL => 'truong',
            default => 'canhan',
        };

        return Excel::download(
            new ConversionActivitiesExport(
                $rows,
                $request->input('from_date'),
                $request->input('to_date'),
                $exportLevel,
            ),
            'thong-ke-hd-chuyen-mon-'.$levelSlug.'-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function exportResearch(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'export_level' => ['nullable', 'in:personal,unit,school'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer'],
        ]);

        $exportLevel = ReportDocumentLayout::normalizeLevel($request->input('export_level'));
        $filters = $request->only(['from_date', 'to_date', 'unit_id', 'unit_ids', 'instructor_id']);

        if ($exportLevel === ReportDocumentLayout::LEVEL_UNIT) {
            $unitIds = ReportDocumentLayout::resolveUnitIds($filters);
            if ($unitIds === []) {
                return back()->with('error', 'Vui lòng chọn ít nhất một khoa khi xuất cấp khoa.');
            }
            $filters['unit_ids'] = $unitIds;
            unset($filters['instructor_id']);
        }

        if ($exportLevel === ReportDocumentLayout::LEVEL_SCHOOL) {
            unset($filters['unit_id'], $filters['unit_ids'], $filters['instructor_id']);
        }

        ManagerUnitScope::applyToFilters($filters);

        $rows = $this->researchReportService->getForExport($filters);

        if ($rows->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu NCKH để xuất.');
        }

        $levelSlug = match ($exportLevel) {
            ReportDocumentLayout::LEVEL_UNIT => 'khoa',
            ReportDocumentLayout::LEVEL_SCHOOL => 'truong',
            default => 'canhan',
        };

        return Excel::download(
            new ResearchHoursStatisticsExport(
                $rows,
                $request->input('from_date'),
                $request->input('to_date'),
                $exportLevel,
            ),
            'thong-ke-nckh-'.$levelSlug.'-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function print(Request $request)
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
        ]);

        $filters = $request->only(['year', 'unit_id', 'unit_ids', 'instructor_id', 'overall_result', 'search']);
        $results = $this->reportService->getForExport($filters);
        $summary = $this->reportService->getSummary($filters);

        if ($results->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu báo cáo để in.');
        }

        return view('standardhours::reports.print', [
            'results' => $results,
            'summary' => $summary,
            'filters' => $filters,
            'autoPrint' => $request->boolean('pdf'),
        ]);
    }
}
