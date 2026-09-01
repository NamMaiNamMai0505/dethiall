<?php

namespace Modules\Building\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Modules\Building\Models\Building;
use Modules\Building\Requests\CreateBuildingRequest;
use Modules\Building\Requests\UpdateBuildingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuildingController extends ModuleBaseController
{
    /**
     * Display a listing of buildings
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = Building::with(['creator', 'updater']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $buildings = $query->paginate(15)->withQueryString();

        return view('building::index', compact('buildings'));
    }

    /**
     * Show the form for creating a new building
     */
    public function create()
    {
        // Permission already checked by middleware
        return view('building::create');
    }

    /**
     * Store a newly created building in storage
     */
    public function store(CreateBuildingRequest $request)
    {
        // Permission already checked by middleware
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? 1;

            Building::create($data);

            return redirect()->route('buildings.index')
                ->with('success', 'Giảng đường đã được tạo thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo giảng đường!');
        }
    }

    /**
     * Display the specified building
     */
    public function show(Building $building)
    {
        // Permission already checked by middleware
        $building->load(['creator', 'updater']);
        return view('building::show', compact('building'));
    }

    /**
     * Show the form for editing the specified building
     */
    public function edit(Building $building)
    {
        // Permission already checked by middleware
        return view('building::edit', compact('building'));
    }

    /**
     * Update the specified building in storage
     */
    public function update(UpdateBuildingRequest $request, Building $building)
    {
        // Permission already checked by middleware
        try {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $building->update($data);

            return redirect()->back()
                ->with('success', 'Giảng đường đã được cập nhật thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật giảng đường!');
        }
    }

    /**
     * Remove the specified building from storage
     */
    public function destroy(Building $building)
    {
        // Permission already checked by middleware
        try {
            $building->delete();

            return redirect()->route('buildings.index')
                ->with('success', 'Giảng đường đã được xóa thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa giảng đường!');
        }
    }
}
