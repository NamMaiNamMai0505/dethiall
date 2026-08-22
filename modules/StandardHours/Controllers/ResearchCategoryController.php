<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Requests\StoreResearchCategoryRequest;
use Modules\StandardHours\Requests\UpdateResearchCategoryRequest;
use Modules\StandardHours\Services\ResearchCategoryService;

class ResearchCategoryController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ResearchCategoryService $researchCategoryService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.research-categories.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $researchCategories = $this->researchCategoryService->paginate($request->all());

        return view('standardhours::research-categories.index', compact('researchCategories'));
    }

    public function create()
    {
        return view('standardhours::research-categories.create');
    }

    public function store(StoreResearchCategoryRequest $request)
    {
        $category = $this->researchCategoryService->create($request->validated());

        return redirect()
            ->route('standard-hours.research-categories.show', $category)
            ->with('success', 'Danh mục NCKH đã được tạo thành công!');
    }

    public function show(ResearchCategory $researchCategory)
    {
        $researchCategory->load(['creator', 'updater']);

        return view('standardhours::research-categories.show', compact('researchCategory'));
    }

    public function edit(ResearchCategory $researchCategory)
    {
        return view('standardhours::research-categories.edit', compact('researchCategory'));
    }

    public function update(UpdateResearchCategoryRequest $request, ResearchCategory $researchCategory)
    {
        $this->researchCategoryService->update($researchCategory, $request->validated());

        return redirect()
            ->route('standard-hours.research-categories.show', $researchCategory)
            ->with('success', 'Danh mục NCKH đã được cập nhật thành công!');
    }

    public function destroy(ResearchCategory $researchCategory)
    {
        try {
            $this->researchCategoryService->delete($researchCategory);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.research-categories.index')
            ->with('success', 'Danh mục NCKH đã được xóa thành công!');
    }

    public function toggleStatus(ResearchCategory $researchCategory)
    {
        $this->researchCategoryService->toggleStatus($researchCategory);

        $status = $researchCategory->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Danh mục NCKH đã được {$status} thành công!");
    }
}
