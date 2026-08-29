<?php

namespace Modules\Classroom\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Building\Models\Building;
use Modules\Classroom\Models\Classroom;
use Modules\Classroom\Requests\CreateClassroomRequest;
use Modules\Classroom\Requests\UpdateClassroomRequest;

class ClassroomController extends ModuleBaseController
{
    /**
     * Display a listing of classrooms
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        // relations creator, updater, building
        $query = Classroom::with(['creator', 'updater', 'building']);
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get per page value (default 10, allow 5, 10, 15, 25, 50)
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $classrooms = $query->paginate($perPage)->withQueryString();

        $buildings = Building::all();

        return view('classroom::index', compact('classrooms', 'buildings'));
    }

    /**
     * Show the form for creating a new classroom
     */
    public function create()
    {
        // Permission already checked by middleware
        // get all building, sort by Name
        $buildings = Building::orderBy('name')->get();

        $units = \Modules\Unit\Models\Unit::active()->orderBy('name')->get();
        return view('classroom::create', compact('buildings', 'units'));
    }

    /**
     * Store a newly created classroom in storage
     */
    public function store(CreateClassroomRequest $request)
    {
        // Permission already checked by middleware
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? true;

            Classroom::create($data);

            return redirect()->route('classrooms.index')
                ->with('success', 'Giảng đường đã được tạo thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo giảng đường!');
        }
    }

    /**
     * Display the specified classroom
     */
    public function show(Classroom $classroom)
    {
        // Permission already checked by middleware

        $classroom->load(['creator', 'updater']);

        return view('classroom::show', compact('classroom'));
    }

    /**
     * Show the form for editing the specified classroom
     */
    public function edit(Classroom $classroom)
    {
        // Permission already checked by middleware
        $buildings = Building::all();
        $units = \Modules\Unit\Models\Unit::active()->orderBy('name')->get();

        return view('classroom::edit', compact('classroom', 'buildings', 'units'));
    }

    /**
     * Update the specified classroom in storage
     */
    public function update(UpdateClassroomRequest $request, Classroom $classroom)
    {
        // Permission already checked by middleware
        try {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            $classroom->update($data);

            return redirect()->back()
                ->with('success', 'Giảng đường đã được cập nhật thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật giảng đường!');
        }
    }

    /**
     * Remove the specified classroom from storage
     */
    public function destroy(Classroom $classroom)
    {
        // Permission already checked by middleware
        try {
            $classroom->delete();

            return redirect()->route('classrooms.index')
                ->with('success', 'Giảng đường đã được xóa thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa giảng đường!');
        }
    }
}
