<?php

namespace Modules\StandardHours\Controllers;

use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\HourExchangeRecord;
use Modules\StandardHours\Requests\StoreHourExchangeRequest;
use Modules\StandardHours\Services\HourExchangeService;
use Modules\StandardHours\Services\PeriodService;
use Modules\StandardHours\Support\InstructorScope;

class HourExchangeController extends StandardHoursBaseController
{
    public function __construct(
        private readonly HourExchangeService $hourExchangeService,
        private readonly PeriodService $periodService
    ) {
        parent::__construct();
        // Xem danh sách/số dư theo quyền Xem; ra quyết định bù giờ theo quyền Thêm.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_VIEW))->only(['balances']);
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_CREATE))->only(['store']);
    }

    public function index(Request $request)
    {
        $years = $this->hourExchangeService->getYears();
        $year = $request->get('year') ?? array_key_first($years);

        $isManager = $this->isManagerActor();
        $units = $isManager ? $this->hourExchangeService->getUnits() : collect();

        $unitId = $isManager ? ($request->integer('unit_id') ?: null) : null;
        $instructors = $this->hourExchangeService->getInstructors($unitId);

        $instructorId = InstructorScope::instructorId()
            ?? ($request->integer('instructor_id') ?: null);

        if ($instructorId && $isManager === false && InstructorScope::instructorId() !== $instructorId) {
            abort(403);
        }

        $balances = null;
        $exchanges = collect();
        $instructor = null;

        if ($instructorId && $year) {
            InstructorScope::ensureOwnsRecord($instructorId);
            $instructor = $instructors->firstWhere('id', $instructorId)
                ?? Instructor::with('unit')->find($instructorId);
            $balances = $this->hourExchangeService->getBalances($instructorId, $year);
            $exchanges = $this->hourExchangeService->recentExchanges($instructorId, $year, 15);
        }

        return view('standardhours::hour-exchanges.index', [
            'years' => $years,
            'year' => $year,
            'periodModeLabel' => $this->periodService->modeLabel(),
            'isManager' => $isManager,
            'units' => $units,
            'unitId' => $unitId,
            'instructors' => $instructors,
            'instructorId' => $instructorId,
            'instructor' => $instructor,
            'balances' => $balances,
            'exchanges' => $exchanges,
            'rates' => [
                HourExchangeRecord::DIRECTION_NCKH_TO_CM => HourExchangeService::NCKH_TO_CM_RATE,
                HourExchangeRecord::DIRECTION_CM_TO_NCKH => HourExchangeService::CM_TO_NCKH_RATE,
            ],
            'directions' => HourExchangeRecord::getDirectionOptions(),
        ]);
    }

    public function store(StoreHourExchangeRequest $request)
    {
        $data = $request->validated();

        abort_unless($this->isManagerActor(), 403, 'Chỉ cấp quản lý được quyết định bù giờ.');
        InstructorScope::ensureOwnsRecord((int) $data['instructor_id']);

        try {
            $record = $this->hourExchangeService->exchange($data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.hour-exchanges.index', [
                'year' => $record->year,
                'instructor_id' => $record->instructor_id,
                'unit_id' => $request->input('unit_id'),
            ])
            ->with('success', 'Đã quy đổi giờ chuẩn thành công!');
    }

    public function balances(Request $request)
    {
        $request->validate([
            'instructor_id' => ['required', 'exists:instructors,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2200'],
        ]);

        $instructorId = (int) $request->input('instructor_id');
        InstructorScope::ensureOwnsRecord($instructorId);

        $balances = $this->hourExchangeService->getBalances(
            $instructorId,
            $request->input('year')
        );

        return response()->json(['data' => $balances]);
    }

    private function isManagerActor(): bool
    {
        $user = auth()->user();

        // Chỉ super-admin / manager được chọn khoa + giảng viên.
        return $user && ($user->isSuperAdmin() || $user->isManager());
    }
}
