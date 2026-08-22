<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\HourNorm;
use Modules\StandardHours\Requests\StoreHourNormRequest;
use Modules\StandardHours\Requests\UpdateHourNormRequest;
use Modules\StandardHours\Services\HourNormService;

class HourNormController extends StandardHoursBaseController
{
    public function __construct(
        private readonly HourNormService $hourNormService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.object-types.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $hourNorms = $this->hourNormService->paginate($request->all());
        $filterOptions = $this->hourNormService->getFilterOptions();

        return view('standardhours::hour-norms.index', array_merge(
            compact('hourNorms'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->hourNormService->getFilterOptions();
        $currentYear = $this->hourNormService->getCurrentYear();

        return view('standardhours::hour-norms.create', array_merge(
            $filterOptions,
            compact('currentYear')
        ));
    }

    public function store(StoreHourNormRequest $request)
    {
        $hourNorm = $this->hourNormService->create($request->validated());

        return redirect()
            ->route('standard-hours.hour-norms.show', $hourNorm)
            ->with('success', 'Định mức giờ chuẩn đã được tạo thành công!');
    }

    public function show(HourNorm $hourNorm)
    {
        $hourNorm->load(['objectType', 'position', 'creator', 'updater']);

        return view('standardhours::hour-norms.show', compact('hourNorm'));
    }

    public function edit(HourNorm $hourNorm)
    {
        $filterOptions = $this->hourNormService->getFilterOptions();

        return view('standardhours::hour-norms.edit', array_merge(
            compact('hourNorm'),
            $filterOptions
        ));
    }

    public function update(UpdateHourNormRequest $request, HourNorm $hourNorm)
    {
        $this->hourNormService->update($hourNorm, $request->validated());

        return redirect()
            ->route('standard-hours.hour-norms.show', $hourNorm)
            ->with('success', 'Định mức giờ chuẩn đã được cập nhật thành công!');
    }

    public function destroy(HourNorm $hourNorm)
    {
        try {
            $this->hourNormService->delete($hourNorm);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.hour-norms.index')
            ->with('success', 'Định mức giờ chuẩn đã được xóa thành công!');
    }

    public function toggleStatus(HourNorm $hourNorm)
    {
        $this->hourNormService->toggleStatus($hourNorm);

        $status = $hourNorm->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Định mức đã được {$status} thành công!");
    }
}
