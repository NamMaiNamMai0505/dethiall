<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\LeaveManagement\Models\{LeaveAuditLog,LeaveLocality,LeaveRequest};

class LeaveRequestEditController extends ModuleBaseController
{
    public function update(Request $r, LeaveRequest $leaveRequest)
    {
        abort_unless(in_array($leaveRequest->status,['PENDING','DRAFT','PENDING_COMMANDER'],true),422,'Chỉ được sửa đơn đang chờ.');
        abort_unless($r->user()->isSuperAdmin() || (int)$leaveRequest->created_by===(int)$r->user()->id || ($leaveRequest->status==='PENDING_COMMANDER' && (int)$leaveRequest->commander_user_id===(int)$r->user()->id),403);
        $d=$r->validate(['from_date'=>'nullable|date','to_date'=>'nullable|date|after_or_equal:from_date','reason'=>'nullable|string','note'=>'nullable|string','travel_days'=>'nullable|integer|min:0','extra_days'=>'nullable|integer|min:0','extra_reasons'=>'nullable|array','locality_id'=>'nullable|exists:leave_localities,id','replacement_personnel_id'=>'nullable|exists:leave_personnel,id']);
        $from=$d['from_date']??$leaveRequest->from_date; $to=$d['to_date']??$leaveRequest->to_date;
        $days=($from&&$to)?Carbon::parse($from)->diffInDays(Carbon::parse($to))+1:($leaveRequest->total_days-(int)$leaveRequest->travel_days-(int)$leaveRequest->extra_days);
        $d['total_days']=$days+(int)($d['travel_days']??$leaveRequest->travel_days)+(int)($d['extra_days']??$leaveRequest->extra_days);
        if(array_key_exists('locality_id',$d))$d['locality_path']=$d['locality_id']?LeaveLocality::find($d['locality_id'])?->name:null;
        $leaveRequest->update($d); LeaveAuditLog::create(['user_id'=>$r->user()->id,'action'=>'UPDATE','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>$d]);
        return back()->with('success','Đã cập nhật đơn nghỉ phép.');
    }
}
