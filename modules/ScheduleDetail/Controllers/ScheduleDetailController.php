<?php

namespace Modules\ScheduleDetail\Controllers;

use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\ScheduleDetail\Requests\CreateScheduleDetailRequest;

class ScheduleDetailController extends ModuleBaseController
{
    public function index()
    {
        $details = ScheduleDetail::with(['subject', 'instructor', 'classroom'])->paginate(20);
        return view('scheduledetail::index', compact('details'));
    }

    public function create()
    {
        return view('scheduledetail::create');
    }

    public function store(CreateScheduleDetailRequest $request)
    {
        ScheduleDetail::create($request->validated());
        return redirect()->route('scheduledetails.index')
                         ->with('success', 'Thêm chi tiết lịch học thành công');
    }
}
