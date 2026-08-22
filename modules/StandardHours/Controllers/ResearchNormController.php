<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\ResearchNorm;
use Modules\StandardHours\Requests\StoreResearchNormRequest;
use Modules\StandardHours\Requests\UpdateResearchNormRequest;
use Modules\StandardHours\Services\ResearchNormService;

class ResearchNormController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ResearchNormService $researchNormService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.object-types.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $researchNorms = $this->researchNormService->paginate($request->all());
        $filterOptions = $this->researchNormService->getFilterOptions();

        return view('standardhours::research-norms.index', array_merge(
            compact('researchNorms'),
            $filterOptions
        ));
    }

    public function create()
    {
        $filterOptions = $this->researchNormService->getFilterOptions();
        $currentYear = $this->researchNormService->getCurrentYear();

        return view('standardhours::research-norms.create', array_merge(
            $filterOptions,
            compact('currentYear')
        ));
    }

    public function store(StoreResearchNormRequest $request)
    {
        $researchNorm = $this->researchNormService->create($request->validated());

        return redirect()
            ->route('standard-hours.research-norms.show', $researchNorm)
            ->with('success', 'Định mức NCKH đã được tạo thành công!');
    }

    public function show(ResearchNorm $researchNorm)
    {
        $researchNorm->load(['objectType', 'creator', 'updater']);

        return view('standardhours::research-norms.show', compact('researchNorm'));
    }

    public function edit(ResearchNorm $researchNorm)
    {
        $filterOptions = $this->researchNormService->getFilterOptions();

        return view('standardhours::research-norms.edit', array_merge(
            compact('researchNorm'),
            $filterOptions
        ));
    }

    public function update(UpdateResearchNormRequest $request, ResearchNorm $researchNorm)
    {
        $this->researchNormService->update($researchNorm, $request->validated());

        return redirect()
            ->route('standard-hours.research-norms.show', $researchNorm)
            ->with('success', 'Định mức NCKH đã được cập nhật thành công!');
    }

    public function destroy(ResearchNorm $researchNorm)
    {
        try {
            $this->researchNormService->delete($researchNorm);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.research-norms.index')
            ->with('success', 'Định mức NCKH đã được xóa thành công!');
    }

    public function toggleStatus(ResearchNorm $researchNorm)
    {
        $this->researchNormService->toggleStatus($researchNorm);

        $status = $researchNorm->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Định mức NCKH đã được {$status} thành công!");
    }
}
