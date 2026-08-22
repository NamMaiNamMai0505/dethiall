<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Requests\StoreObjectTypeRequest;
use Modules\StandardHours\Requests\UpdateObjectTypeRequest;
use Modules\StandardHours\Services\ObjectTypeService;

class ObjectTypeController extends StandardHoursBaseController
{
    public function __construct(
        private readonly ObjectTypeService $objectTypeService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.object-types.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $objectTypes = $this->objectTypeService->paginate($request->all());

        return view('standardhours::object-types.index', compact('objectTypes'));
    }

    public function create()
    {
        return view('standardhours::object-types.create');
    }

    public function store(StoreObjectTypeRequest $request)
    {
        $objectType = $this->objectTypeService->create($request->validated());

        return redirect()
            ->route('standard-hours.object-types.show', $objectType)
            ->with('success', 'Đối tượng đã được tạo thành công!');
    }

    public function show(ObjectType $objectType)
    {
        $objectType->load(['creator', 'updater']);

        return view('standardhours::object-types.show', compact('objectType'));
    }

    public function edit(ObjectType $objectType)
    {
        return view('standardhours::object-types.edit', compact('objectType'));
    }

    public function update(UpdateObjectTypeRequest $request, ObjectType $objectType)
    {
        $this->objectTypeService->update($objectType, $request->validated());

        return redirect()
            ->route('standard-hours.object-types.show', $objectType)
            ->with('success', 'Đối tượng đã được cập nhật thành công!');
    }

    public function destroy(ObjectType $objectType)
    {
        try {
            $this->objectTypeService->delete($objectType);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.object-types.index')
            ->with('success', 'Đối tượng đã được xóa thành công!');
    }

    public function toggleStatus(ObjectType $objectType)
    {
        $this->objectTypeService->toggleStatus($objectType);

        $status = $objectType->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Đối tượng đã được {$status} thành công!");
    }
}
