<?php
namespace Modules\LeaveManagement\Controllers;

use Illuminate\Http\Request;
use Modules\LeaveManagement\Models\LeaveObjectType;
use Modules\LeaveManagement\Models\LeaveRegulation;

class LeaveRegulationDashboardController
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString() ?: 'objects';
        return view('leave-management::regulations-dashboard', [
            'tab' => $tab,
            'objects' => LeaveObjectType::where('active', true)->orderBy('sort_order')->get(),
            'regulations' => LeaveRegulation::where('active', true)->orderBy('object_type')->orderBy('min_years')->get(),
        ]);
    }
}
