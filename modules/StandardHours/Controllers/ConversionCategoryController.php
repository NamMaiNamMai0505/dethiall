<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Requests\StoreConversionCategoryRequest;
use Modules\StandardHours\Requests\UpdateConversionCategoryRequest;
use Modules\StandardHours\Services\ConversionCategoryService;

class ConversionCategoryController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ConversionCategoryService $conversionCategoryService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.conversion-categories.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $conversionCategories = $this->conversionCategoryService->paginate($request->all());
        $conversionMethods = ConversionCategory::getConversionMethodOptions();

        return view('standardhours::conversion-categories.index', compact(
            'conversionCategories',
            'conversionMethods'
        ));
    }

    public function create()
    {
        $conversionMethods = ConversionCategory::getConversionMethodOptions();

        return view('standardhours::conversion-categories.create', compact('conversionMethods'));
    }

    public function store(StoreConversionCategoryRequest $request)
    {
        $category = $this->conversionCategoryService->create($request->validated());

        return redirect()
            ->route('standard-hours.conversion-categories.show', $category)
            ->with('success', 'Danh mục hoạt động chuyên môn đã được tạo thành công!');
    }

    public function show(ConversionCategory $conversionCategory)
    {
        $conversionCategory->load(['creator', 'updater']);

        return view('standardhours::conversion-categories.show', compact('conversionCategory'));
    }

    public function edit(ConversionCategory $conversionCategory)
    {
        $conversionMethods = ConversionCategory::getConversionMethodOptions();

        return view('standardhours::conversion-categories.edit', compact(
            'conversionCategory',
            'conversionMethods'
        ));
    }

    public function update(UpdateConversionCategoryRequest $request, ConversionCategory $conversionCategory)
    {
        $this->conversionCategoryService->update($conversionCategory, $request->validated());

        return redirect()
            ->route('standard-hours.conversion-categories.show', $conversionCategory)
            ->with('success', 'Danh mục hoạt động chuyên môn đã được cập nhật thành công!');
    }

    public function destroy(ConversionCategory $conversionCategory)
    {
        try {
            $this->conversionCategoryService->delete($conversionCategory);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.conversion-categories.index')
            ->with('success', 'Danh mục hoạt động chuyên môn đã được xóa thành công!');
    }

    public function toggleStatus(ConversionCategory $conversionCategory)
    {
        $this->conversionCategoryService->toggleStatus($conversionCategory);

        $status = $conversionCategory->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Danh mục đã được {$status} thành công!");
    }
}
