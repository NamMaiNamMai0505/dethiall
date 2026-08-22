<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Services\HubService;

/**
 * Giờ chuẩn GV — bản sao native trong shell LMS (chỉ phần tự phục vụ GV).
 * Không đụng modules/StandardHours — chỉ tái sử dụng Service qua DI.
 */
class HubController extends Controller
{
    public function __construct(
        private readonly HubService $hubService
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index()
    {
        if (! $this->standardHoursTablesReady()) {
            return view('lms::teach.standard-hours.hub.index', [
                'stats' => $this->emptyStats(),
                'setupWarning' => 'Chưa có đủ bảng dữ liệu giờ chuẩn. Liên hệ Phòng Đào tạo để khởi tạo module.',
            ]);
        }

        try {
            $stats = $this->hubService->getSummaryStats();
        } catch (\Throwable $e) {
            Log::error('LMS StandardHours hub failed', ['error' => $e->getMessage()]);

            return view('lms::teach.standard-hours.hub.index', [
                'stats' => $this->emptyStats(),
                'setupWarning' => 'Không tải được thống kê giờ chuẩn: '.$e->getMessage(),
            ]);
        }

        return view('lms::teach.standard-hours.hub.index', [
            'stats' => $stats,
            'setupWarning' => null,
        ]);
    }

    public function guide()
    {
        return view('lms::teach.standard-hours.guide.index');
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
}
