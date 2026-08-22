<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Requests\StorePositionRequest;
use Modules\StandardHours\Requests\UpdatePositionRequest;
use Modules\StandardHours\Services\PositionService;

class PositionController extends StandardHoursBaseController
{
    public function __construct(
        private readonly PositionService $positionService
    ) {
        parent::__construct();
        $this->middleware('permission:standard-hours.positions.manage')->only(['toggleStatus']);
    }

    public function index(Request $request)
    {
        $positions = $this->positionService->paginate($request->all());

        return view('standardhours::positions.index', compact('positions'));
    }

    public function create()
    {
        return view('standardhours::positions.create');
    }

    public function store(StorePositionRequest $request)
    {
        $position = $this->positionService->create($request->validated());

        return redirect()
            ->route('standard-hours.positions.show', $position)
            ->with('success', 'Chức danh đã được tạo thành công!');
    }

    public function show(Position $position)
    {
        $position->load(['creator', 'updater']);

        return view('standardhours::positions.show', compact('position'));
    }

    public function edit(Position $position)
    {
        return view('standardhours::positions.edit', compact('position'));
    }

    public function update(UpdatePositionRequest $request, Position $position)
    {
        $this->positionService->update($position, $request->validated());

        return redirect()
            ->route('standard-hours.positions.show', $position)
            ->with('success', 'Chức danh đã được cập nhật thành công!');
    }

    public function destroy(Position $position)
    {
        try {
            $this->positionService->delete($position);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('standard-hours.positions.index')
            ->with('success', 'Chức danh đã được xóa thành công!');
    }

    public function toggleStatus(Position $position)
    {
        $this->positionService->toggleStatus($position);

        $status = $position->fresh()->is_active ? 'kích hoạt' : 'ngừng sử dụng';

        return redirect()
            ->back()
            ->with('success', "Chức danh đã được {$status} thành công!");
    }
}
