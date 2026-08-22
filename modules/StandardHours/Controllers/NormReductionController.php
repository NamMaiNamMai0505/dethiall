<?php

namespace Modules\StandardHours\Controllers;

use Illuminate\Http\Request;
use Modules\StandardHours\Models\InstructorNormReduction;
use Modules\StandardHours\Services\HourNormService;
use Modules\StandardHours\Services\NormReductionService;

/**
 * Điều 11.3 — giảm trừ định mức GC (đột xuất / ốm / thai sản).
 */
class NormReductionController extends StandardHoursBaseController
{
    public function __construct(
        private readonly NormReductionService $service,
        private readonly HourNormService $hourNorms,
    ) {
        parent::__construct();
        // store/update/destroy đã được base controller gác theo Thêm/Sửa/Xóa.
    }

    public function index(Request $request)
    {
        $rows = $this->service->paginate($request->all());
        $years = $this->hourNorms->getYears();
        $instructors = $this->service->instructors();
        $types = InstructorNormReduction::typeLabels();

        return view('standardhours::norm-reductions.index', compact('rows', 'years', 'instructors', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'year' => 'required|integer|min:2000|max:2200',
            'type' => 'required|in:special_duty,sick_leave,maternity,other',
            'title' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'days' => 'nullable|integer|min:0|max:366',
            'reduction_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $this->service->create($data);

        return back()->with('success', 'Đã ghi giảm trừ định mức (Đ.11.3). Sẽ áp khi tính giờ chuẩn.');
    }

    public function destroy(InstructorNormReduction $reduction)
    {
        $this->service->delete($reduction);

        return back()->with('success', 'Đã xóa bản ghi giảm trừ.');
    }
}
