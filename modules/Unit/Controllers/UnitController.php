<?php

namespace Modules\Unit\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Unit\Models\Unit;
use Modules\Unit\Requests\CreateUnitRequest;
use Modules\Unit\Requests\UpdateUnitRequest;

class UnitController extends ModuleBaseController
{
    /**
     * Display a listing of units
     */
    public function index(Request $request)
    {
        // Permission already checked by middleware
        $query = Unit::with(['parent', 'creator']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        // Apply parent filter
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('functional_type')) {
            $query->where('functional_type', $request->functional_type);
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

        $units = $query->paginate($perPage)->withQueryString();

        // Get parent units for filter
        $parentUnits = Unit::whereNull('parent_id')->active()->get();

        return view('unit::index', compact('units', 'parentUnits'));
    }

    /**
     * Show the form for creating a new unit
     */
    public function create()
    {
        // Permission already checked by middleware
        // Get parent units for dropdown
        $parentUnits = Unit::active()->get();

        return view('unit::create', compact('parentUnits'));
    }

    /**
     * Store a newly created unit
     */
    public function store(CreateUnitRequest $request)
    {
        // Permission already checked by middleware
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = Auth::id();
            // Mã khoa = chính Mã đơn vị khi đơn vị là Khoa chuyên môn — không
            // hỏi lại một mã riêng, và không giới hạn cứng vào danh sách K1-K8
            // (Khoa mới sau này dùng mã gì cũng được). Xem
            // docs/sprints/SPRINT44_LMS_AND_SCOPE_UX.md §2.2.
            $data['faculty_code'] = $data['functional_type'] === Unit::FUNCTIONAL_FACULTY
                ? $data['code']
                : null;

            // Calculate level
            $data['level'] = 1;
            if ($request->filled('parent_id')) {
                $parent = Unit::findOrFail($request->parent_id);
                $data['level'] = $parent->level + 1;
            }

            $unit = Unit::create($data);

            DB::commit();

            return redirect()
                ->route('units.show', $unit)
                ->with('success', 'Đơn vị đã được tạo thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo đơn vị: '.$e->getMessage());
        }
    }

    /**
     * Display the specified unit
     */
    public function show(Unit $unit)
    {
        // Permission already checked by middleware
        $unit->load(['parent', 'children', 'creator', 'updater']);

        return view('unit::show', compact('unit'));
    }

    /**
     * Show the form for editing the specified unit
     */
    public function edit(Unit $unit)
    {
        // Permission already checked by middleware
        // Get parent units for dropdown, excluding self and descendants
        $parentUnits = Unit::active()
            ->where('id', '!=', $unit->id)
            ->whereNotIn('id', $unit->descendants->pluck('id'))
            ->get();

        return view('unit::edit', compact('unit', 'parentUnits'));
    }

    /**
     * Update the specified unit
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        // Permission already checked by middleware
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['updated_by'] = Auth::id();
            $data['faculty_code'] = $data['functional_type'] === Unit::FUNCTIONAL_FACULTY
                ? $data['code']
                : null;

            // Calculate new level if parent changed
            if ($request->filled('parent_id') && $request->parent_id != $unit->parent_id) {
                $parent = Unit::findOrFail($request->parent_id);
                $data['level'] = $parent->level + 1;

                // Update descendants levels
                $levelDiff = $data['level'] - $unit->level;
                if ($levelDiff != 0) {
                    foreach ($unit->descendants as $descendant) {
                        $descendant->update([
                            'level' => $descendant->level + $levelDiff,
                        ]);
                    }
                }
            }

            $unit->update($data);

            DB::commit();

            return redirect()
                ->route('units.show', $unit)
                ->with('success', 'Đơn vị đã được cập nhật thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật đơn vị: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified unit
     */
    public function destroy(Unit $unit)
    {
        // Permission already checked by middleware
        try {
            DB::beginTransaction();

            // Check if unit has children
            if ($unit->children()->exists()) {
                throw new \Exception('Không thể xóa đơn vị có đơn vị con.');
            }

            // Khoa bị xoá không kéo theo Môn - Môn thuộc khoa này chỉ mất
            // gán (faculty_code = null), bản thân Môn vẫn giữ nguyên.
            $unassignedSubjects = 0;
            if (! empty($unit->faculty_code)) {
                $unassignedSubjects = \Modules\Subject\Models\Subject::where('faculty_code', $unit->faculty_code)
                    ->update(['faculty_code' => null]);
            }

            $unit->delete();

            DB::commit();

            $message = 'Đơn vị đã được xóa thành công!';
            if ($unassignedSubjects > 0) {
                $message .= " {$unassignedSubjects} môn học thuộc khoa này đã được gỡ gán (không bị xóa).";
            }

            return redirect()
                ->route('units.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Có lỗi xảy ra khi xóa đơn vị: '.$e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Unit $unit)
    {
        try {
            DB::beginTransaction();

            $unit->update([
                'status' => $unit->status === 'active' ? 'inactive' : 'active',
                'updated_by' => Auth::id(),
            ]);

            // If deactivating, also deactivate all children
            if ($unit->status === 'inactive') {
                $unit->descendants()->update([
                    'status' => 'inactive',
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            $status = $unit->status === 'active' ? 'kích hoạt' : 'tạm ngừng';

            return redirect()
                ->back()
                ->with('success', "Đơn vị đã được {$status} thành công!");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Có lỗi xảy ra khi thay đổi trạng thái đơn vị: '.$e->getMessage());
        }
    }
}
