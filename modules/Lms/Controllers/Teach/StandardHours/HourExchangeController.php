<?php

namespace Modules\Lms\Controllers\Teach\StandardHours;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Lms\Support\LmsAccess;
use Modules\StandardHours\Models\HourExchangeRecord;
use Modules\StandardHours\Services\HourExchangeService;
use Modules\StandardHours\Services\PeriodService;
use Modules\StandardHours\Support\InstructorScope;

/**
 * Quy đổi giờ NCKH ↔ HĐCM — bản sao native trong shell LMS, CHỈ XEM
 * (số dư + lịch sử) cho GV tự tra cứu. Quyết định bù giờ (store) luôn là
 * đặc quyền của cấp quản lý ở modules/StandardHours (HourExchangeController::store
 * chặn bằng isManagerActor()) — không mở form tạo ở đây để tránh 403 gây nhầm lẫn.
 * Không đụng modules/StandardHours — chỉ tái sử dụng Service qua DI.
 */
class HourExchangeController extends Controller
{
    public function __construct(
        private readonly HourExchangeService $hourExchangeService,
        private readonly PeriodService $periodService
    ) {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            abort_unless(LmsAccess::isInstructorUser($request->user()), 403, 'Chỉ dành cho tài khoản giảng viên.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $instructorId = InstructorScope::ensureInstructorUser();

        $years = $this->hourExchangeService->getYears();
        $year = $request->get('year') ?? array_key_first($years);

        $balances = null;
        $exchanges = collect();

        if ($year) {
            $balances = $this->hourExchangeService->getBalances($instructorId, $year);
            $exchanges = $this->hourExchangeService->recentExchanges($instructorId, $year, 15);
        }

        return view('lms::teach.standard-hours.hour-exchanges.index', [
            'years' => $years,
            'year' => $year,
            'periodModeLabel' => $this->periodService->modeLabel(),
            'balances' => $balances,
            'exchanges' => $exchanges,
            'rates' => [
                HourExchangeRecord::DIRECTION_NCKH_TO_CM => HourExchangeService::NCKH_TO_CM_RATE,
                HourExchangeRecord::DIRECTION_CM_TO_NCKH => HourExchangeService::CM_TO_NCKH_RATE,
            ],
        ]);
    }
}
