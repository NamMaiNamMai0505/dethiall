<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApprovalAgency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\StandardHours\Services\HubService;

class HubController extends StandardHoursBaseController
{
    protected bool $allowInstructorAccess = true;

    public function __construct(
        private readonly HubService $hubService
    ) {
        parent::__construct();
    }

    public function index()
    {
        if (ApprovalAgency::isResearchAgency(auth()->user())) {
            return redirect()->route('standard-hours.research-records.index');
        }

        $isInstructorOnly = auth()->user()?->hasRole('instructor') ?? false;
        $isResearchRestricted = ApprovalAgency::isStandardHoursGradesAgency(auth()->user());

        // Tránh 500 khi production chưa migrate đủ bảng giờ chuẩn
        if (! $this->standardHoursTablesReady()) {
            return view('standardhours::hub.index', [
                'stats' => $this->emptyStats(),
                'chartData' => $this->emptyChartData(),
                'isInstructorOnly' => $isInstructorOnly,
                'isResearchRestricted' => $isResearchRestricted,
                'setupWarning' => 'Chưa có đủ bảng dữ liệu giờ chuẩn. Chạy migration + seed module StandardHours trên server.',
            ]);
        }

        try {
            $stats = $this->hubService->getSummaryStats();
            $chartData = $this->hubService->getChartData();
        } catch (\Throwable $e) {
            Log::error('StandardHours hub failed', ['error' => $e->getMessage()]);

            return view('standardhours::hub.index', [
                'stats' => $this->emptyStats(),
                'chartData' => $this->emptyChartData(),
                'isInstructorOnly' => $isInstructorOnly,
                'isResearchRestricted' => $isResearchRestricted,
                'setupWarning' => 'Không tải được thống kê giờ chuẩn: '.$e->getMessage(),
            ]);
        }

        if ($isResearchRestricted) {
            $stats['research'] = $this->emptyStats()['research'];
            $chartData['research_by_category'] = [
                'labels' => [],
                'hours' => [],
                'colors' => [],
            ];
        }

        return view('standardhours::hub.index', compact(
            'stats',
            'chartData',
            'isInstructorOnly',
            'isResearchRestricted'
        ));
    }

    public function guide()
    {
        return view('standardhours::guide.index', [
            'isInstructor' => auth()->user()?->hasRole('instructor') ?? false,
            'isReviewer' => ApprovalAgency::canReviewResearch(auth()->user())
                || ApprovalAgency::canReviewProfessionalActivities(auth()->user()),
        ]);
    }

    private function standardHoursTablesReady(): bool
    {
        try {
            return Schema::hasTable('instructor_conversion_records')
                && Schema::hasTable('instructor_research_records')
                && Schema::hasTable('instructor_external_activity_records')
                && Schema::hasTable('yearly_standard_results');
        } catch (\Throwable) {
            return false;
        }
    }

    private function emptyStats(): array
    {
        $zeroBlock = [
            'total' => 0,
            'approved' => 0,
            'submitted' => 0,
            'draft' => 0,
            'total_hours' => 0,
        ];

        return [
            'conversion' => $zeroBlock,
            'research' => $zeroBlock,
            'external' => $zeroBlock,
            'calculated' => ['total' => 0, 'passed' => 0],
        ];
    }

    private function emptyChartData(): array
    {
        return [
            'year' => null,
            'top_instructors' => ['labels' => [], 'hours' => [], 'source' => 'empty'],
            'conversion_by_category' => ['labels' => [], 'hours' => [], 'colors' => []],
            'research_by_category' => ['labels' => [], 'hours' => [], 'colors' => []],
        ];
    }
}
