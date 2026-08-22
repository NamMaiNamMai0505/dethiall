<?php

namespace Modules\StandardHours\Controllers;

use App\Support\AcademicYearCatalog;
use App\Support\ApplicationRegistry;
use Modules\StandardHours\Requests\UpdatePeriodModeRequest;
use Modules\StandardHours\Requests\UpdateResearchDistributionRulesRequest;
use Modules\StandardHours\Services\PeriodService;
use Modules\StandardHours\Services\ResearchDistributionService;

class SettingsController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ResearchDistributionService $distributionService,
        private readonly PeriodService $periodService
    ) {
        parent::__construct();

        // "Kỳ tính năm học" và "Luật quy đổi" là hai ứng dụng riêng trong ma
        // trận phân quyền — gác từng trang thay vì một quyền cài đặt gộp.
        $this->middleware($this->authorizeSettingsApplication(
            'standard-hours.settings.period-mode',
            ApplicationRegistry::ACTION_VIEW
        ))->only(['editPeriodMode']);
        $this->middleware($this->authorizeSettingsApplication(
            'standard-hours.settings.period-mode',
            ApplicationRegistry::ACTION_EDIT
        ))->only(['updatePeriodMode']);
        $this->middleware($this->authorizeSettingsApplication(
            'standard-hours.settings.research-rules',
            ApplicationRegistry::ACTION_VIEW
        ))->only(['editResearchRules']);
        $this->middleware($this->authorizeSettingsApplication(
            'standard-hours.settings.research-rules',
            ApplicationRegistry::ACTION_EDIT
        ))->only(['updateResearchRules']);
    }

    /**
     * Quyền chi tiết của trang cài đặt, kèm quyền gộp `settings.manage` cũ.
     */
    private function authorizeSettingsApplication(string $application, string $action): \Closure
    {
        $abilities = array_merge(
            ApplicationRegistry::permissionNamesFor($application, $action),
            ['standard-hours.settings.manage']
        );

        return function ($request, $next) use ($abilities) {
            $user = $request->user();
            abort_unless(
                $user !== null && collect($abilities)->contains(fn (string $ability) => $user->can($ability)),
                403
            );

            return $next($request);
        };
    }

    public function editResearchRules()
    {
        $rules = $this->distributionService->getRules();

        return view('standardhours::settings.research-rules', compact('rules'));
    }

    public function updateResearchRules(UpdateResearchDistributionRulesRequest $request)
    {
        $this->distributionService->saveRules($request->validated('rules'), auth()->id());

        return redirect()
            ->route('standard-hours.settings.research-rules')
            ->with('success', 'Luật quy đổi NCKH đã được cập nhật và áp dụng ngay!');
    }

    public function editPeriodMode()
    {
        $mode = $this->periodService->mode();
        $samples = [];

        foreach (PeriodService::MODES as $periodMode) {
            $year = $periodMode === PeriodService::MODE_ACADEMIC_YEAR
                ? $this->academicCurrentYear()
                : now()->year;
            [$fromDate, $toDate] = $this->periodService->dateRange($year, $periodMode);
            $samples[$periodMode] = [
                'label' => $this->periodService->label($year, $periodMode),
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ];
        }

        return view('standardhours::settings.period-mode', compact('mode', 'samples'));
    }

    public function updatePeriodMode(UpdatePeriodModeRequest $request)
    {
        $this->periodService->setMode($request->validated('period_mode'), auth()->id());

        return redirect()
            ->route('standard-hours.settings.period-mode')
            ->with(
                'success',
                'Đã chuyển kỳ tính Giờ chuẩn GV sang '
                    .$this->periodService->modeLabel().'. Các module khác không bị thay đổi.'
            )
            ->setStatusCode(303);
    }

    private function academicCurrentYear(): int
    {
        $code = AcademicYearCatalog::currentCode();

        return preg_match('/^(\d{4})-\d{4}$/', $code, $matches)
            ? (int) $matches[1]
            : (now()->month >= 8 ? now()->year : now()->year - 1);
    }
}
