<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\LeaveManagement\Models\{LeaveAuditLog,LeaveLocality,LeavePersonnel,LeaveRegulation,LeaveRequest};
use Modules\LeaveManagement\Support\LeaveAccess;

class LeaveRequestEditController extends ModuleBaseController
{
    public function update(Request $r, LeaveRequest $leaveRequest)
    {
        abort_unless(in_array($leaveRequest->status,['PENDING','DRAFT','PENDING_COMMANDER','PENDING_AGENCY'],true),422,'Chỉ được sửa đơn đang chờ.');
        $isAgency=$leaveRequest->status==='PENDING_AGENCY' && LeaveAccess::canHandleAgency((string)$leaveRequest->managing_agency,$r->user());
        abort_unless($r->user()->isSuperAdmin() || (int)$leaveRequest->created_by===(int)$r->user()->id || ($leaveRequest->status==='PENDING_COMMANDER' && (int)$leaveRequest->commander_user_id===(int)$r->user()->id) || $isAgency,403);
        $d=$r->validate(['leave_type'=>'nullable|in:ANNUAL,EXTRA,SICK,PERSONAL,SHORT_LEAVE','from_date'=>'nullable|date','to_date'=>'nullable|date|after_or_equal:from_date','reason'=>'nullable|string','note'=>'nullable|string','travel_days'=>'nullable|integer|min:0','extra_standard_ids_marker'=>'nullable|boolean','extra_standard_ids'=>'nullable|array','extra_standard_ids.*'=>'integer|exists:leave_regulations,id','extra_days'=>'nullable|integer|min:0','extra_reasons'=>'nullable|array','locality_id'=>'nullable|exists:leave_localities,id','replacement_personnel_id'=>'nullable|exists:leave_personnel,id']);
        if (!empty($d['replacement_personnel_id'])) {
            $replacement = LeavePersonnel::withoutGlobalScopes()->where('active', true)->findOrFail($d['replacement_personnel_id']);
            abort_unless((int) $replacement->unit_id === (int) $leaveRequest->unit_id, 422, 'Người thay thế phải thuộc cùng đơn vị với quân nhân nghỉ.');
        }
        $from=$d['from_date']??$leaveRequest->from_date; $to=$d['to_date']??$leaveRequest->to_date;
        $days=($from&&$to)?Carbon::parse($from)->diffInDays(Carbon::parse($to))+1:0;
        $leaveType=$d['leave_type']??$leaveRequest->leave_type;
        $baseDays=(int)$leaveRequest->base_days;
        if ($leaveType !== $leaveRequest->leave_type && $leaveRequest->personnel) {
            $serviceYears=$leaveRequest->personnel->enlistment_date ? max(0, now()->year - Carbon::parse($leaveRequest->personnel->enlistment_date)->year) : 0;
            $baseDays=(int)(LeaveRegulation::where('leave_type',$leaveType)->where('active',true)->where(function($q)use($leaveRequest){$q->whereNull('object_type')->orWhere('object_type',$leaveRequest->personnel->object_type);})->where(function($q)use($serviceYears){$q->whereNull('min_years')->orWhere('min_years','<=',$serviceYears);})->where(function($q)use($serviceYears){$q->whereNull('max_years')->orWhere('max_years','>=',$serviceYears);})->orderByDesc('min_years')->value('base_days') ?? 0);
            $d['base_days']=$baseDays;
        }
        $extraStandards=LeaveRegulation::where('leave_type','EXTRA')->where('active',true)->whereIn('id',$d['extra_standard_ids']??[])->get();
        $d['extra_days']=$r->boolean('extra_standard_ids_marker') ? $extraStandards->sum('base_days') : (int)($d['extra_days']??$leaveRequest->extra_days);
        $d['extra_reasons']=$extraStandards->map(fn($standard)=>['id'=>$standard->id,'label'=>$standard->description?:$standard->label,'days'=>(int)$standard->base_days])->values()->all();
        unset($d['extra_standard_ids'],$d['extra_standard_ids_marker']);
        $travelDays=(int)($d['travel_days']??$leaveRequest->travel_days);
        $d['total_days']=($leaveType==='ANNUAL' ? $baseDays : $days)+(int)$travelDays+(int)$d['extra_days'];
        if ($from && $leaveType==='ANNUAL') {
            $d['to_date']=Carbon::parse($from)->addDays(max(0,(int)$d['total_days']-1))->toDateString();
        }
        if(array_key_exists('locality_id',$d))$d['locality_path']=$d['locality_id']?LeaveLocality::find($d['locality_id'])?->name:null;
        $leaveRequest->update($d); LeaveAuditLog::create(['user_id'=>$r->user()->id,'action'=>'UPDATE','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>$d]);
        return back()->with('success','Đã cập nhật đơn nghỉ phép.');
    }
}
