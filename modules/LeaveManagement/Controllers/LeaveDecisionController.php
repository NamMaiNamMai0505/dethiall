<?php

namespace Modules\LeaveManagement\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\PermissionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\LeaveManagement\Models\{LeaveAlert,LeaveAuditLog,LeaveBatch,LeaveMailLog,LeaveRecord,LeaveRequest};
use Modules\LeaveManagement\Support\LeaveAccess;

class LeaveDecisionController extends ModuleBaseController
{
    public function decide(Request $request, LeaveRequest $leaveRequest)
    {
        $user=$request->user();
        $decision=$request->validate(['status'=>'required|in:PENDING_AGENCY,PENDING_HEAD,APPROVED','decision_note'=>'nullable|string|max:2000','bgh_signed'=>'nullable|boolean','bgh_note'=>'nullable|string|max:2000']);
        $status=$leaveRequest->status==='PENDING'?'PENDING_COMMANDER':$leaveRequest->status;

        if ($status==='PENDING_COMMANDER') {
            abort_unless(LeaveAccess::canCommandRequest($leaveRequest->loadMissing('personnel'),$user),403,'Chỉ chỉ huy cơ quan/đơn vị theo cây đơn vị được xử lý bước này.');
            abort_unless($decision['status']==='PENDING_AGENCY',422,'Đơn đang chờ chỉ huy đề nghị nghỉ phép hoặc trả lại.');
            $leaveRequest->update(['status'=>$decision['status'],'decision_note'=>$decision['decision_note']??null,'decided_by_user_id'=>$user->id,'decided_by_username'=>$user->email,'decided_at'=>now()]);
            LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'COMMANDER_PROPOSED_LEAVE','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>$decision]);
            $this->notifyManagement($leaveRequest);
            return back()->with('success','Đã đề nghị nghỉ phép và chuyển đơn lên cơ quan cán bộ hoặc Quân lực theo đối tượng.');
        }

        if ($status==='PENDING_AGENCY') {
            abort_unless(LeaveAccess::canHandleAgency((string) $leaveRequest->managing_agency, $user),403,'Tài khoản không thuộc cơ quan quản lý của quân nhân này.');
            abort_unless($decision['status']==='PENDING_HEAD',422,'Cơ quan cán bộ hoặc Quân lực chỉ được trình ký hoặc trả lại đơn.');

            abort_unless($leaveRequest->printed_at,422,'Phải in đơn trước khi trình thủ trưởng ký.');
            $leaveRequest->update(['status'=>'PENDING_HEAD','decision_note'=>$decision['decision_note']??null,'decided_by_user_id'=>$user->id,'decided_by_username'=>$user->email,'decided_at'=>now()]);
            LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'AGENCY_SUBMITTED_TO_HEAD','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>$decision]);
            $this->notifyHeadSigners($leaveRequest);
            return back()->with('success','Đã thẩm định và trình thủ trưởng ký.');
        }

        abort_unless($status==='PENDING_HEAD',422,'Đơn không ở bước chờ thủ trưởng ký.');
        abort_unless(LeaveAccess::canHeadSign($user),403,'Chỉ thủ trưởng/Ban Giám hiệu được ký bước này.');
        abort_unless($decision['status']==='APPROVED',422,'Thủ trưởng chỉ được ký duyệt hoặc trả lại đơn.');

        abort_unless($request->boolean('bgh_signed') || $leaveRequest->bgh_signed_at,422,'Chưa xác nhận thủ trưởng/Ban Giám hiệu đã ký.');
        DB::transaction(function () use ($leaveRequest,$user,$decision): void {
            if (!$leaveRequest->bgh_signed_at) {
                $leaveRequest->bgh_signed_at=now();
                $leaveRequest->bgh_signed_by_user_id=$user->id;
                $leaveRequest->bgh_note=$decision['bgh_note']??null;
            }
            $leaveRequest->status='APPROVED';
            $leaveRequest->approved_by=$user->id;
            $leaveRequest->approved_at=now();
            $leaveRequest->decided_by_user_id=$user->id;
            $leaveRequest->decided_by_username=$user->email;
            $leaveRequest->decided_at=now();
            $leaveRequest->decision_note=$decision['decision_note']??null;
            $leaveRequest->save();
            $recordData=[
                'personnel_id'=>$leaveRequest->personnel_id,'personnel_code'=>$leaveRequest->personnel_code,'personnel_name'=>$leaveRequest->personnel_name,
                'status'=>'APPROVED','leave_type'=>$leaveRequest->leave_type,'object_type'=>$leaveRequest->object_type,'rank'=>$leaveRequest->rank,'position'=>$leaveRequest->position,
                'enlistment_date'=>$leaveRequest->enlistment_date,'unit_id'=>$leaveRequest->unit_id,'unit_name'=>$leaveRequest->unit_name,'service_years'=>$leaveRequest->service_years,
                'base_days'=>$leaveRequest->base_days,'travel_days'=>$leaveRequest->travel_days,'extra_days'=>$leaveRequest->extra_days,'extra_reasons'=>$leaveRequest->extra_reasons,
                'total_days'=>$leaveRequest->total_days,'start_date'=>$leaveRequest->from_date,'end_date'=>$leaveRequest->to_date,'leave_year'=>$leaveRequest->leave_year,
                'locality_id'=>$leaveRequest->locality_id,'locality_path'=>$leaveRequest->locality_path,'note'=>$leaveRequest->note,'admin_note'=>$leaveRequest->admin_note,
                'proposed_by_user_id'=>$leaveRequest->proposed_by_user_id,'proposed_by_username'=>$leaveRequest->proposed_by_username,'proposed_by_display_name'=>$leaveRequest->proposed_by_display_name,
                'decided_by_user_id'=>$user->id,'decided_by_username'=>$user->email,'decided_at'=>now(),
            ];
            if (Schema::hasColumn('leave_records','replacement_personnel_id')) {
                $recordData['replacement_personnel_id']=$leaveRequest->replacement_personnel_id;
                $recordData['replacement_personnel_name']=$leaveRequest->replacement_personnel_name;
                $recordData['replacement_position']=$leaveRequest->replacement_position;
            }
            LeaveRecord::updateOrCreate(['request_id'=>$leaveRequest->id],$recordData);
            LeaveBatch::firstOrCreate(['request_id'=>$leaveRequest->id],['personnel_id'=>$leaveRequest->personnel_id,'personnel_code'=>$leaveRequest->personnel_code,'personnel_name'=>$leaveRequest->personnel_name,'leave_type'=>$leaveRequest->leave_type,'batch_label'=>'Đơn nghỉ phép #'.$leaveRequest->id,'label'=>'Đơn nghỉ phép #'.$leaveRequest->id,'start_date'=>$leaveRequest->from_date,'end_date'=>$leaveRequest->to_date,'total_days'=>$leaveRequest->total_days,'created_by'=>$user->id,'created_by_user_id'=>$user->id]);
            LeaveAuditLog::create(['user_id'=>$user->id,'action'=>'HEAD_SIGNED','entity_type'=>'request','entity_id'=>$leaveRequest->id,'details'=>$decision]);
        });
        $this->notifyManagement($leaveRequest,'Đơn nghỉ phép #'.$leaveRequest->id.' đã được thủ trưởng/Ban Giám hiệu ký.');
        $this->notifyProposer($leaveRequest,'Đề xuất nghỉ phép đã được Ban Giám hiệu ký và cơ quan quản lý duyệt.');
        $this->sendFinalApprovalMail($leaveRequest);
        return back()->with('success','Đã duyệt phép cuối cùng và gửi thông báo cho quân nhân.');
    }

    /**
     * Trả hồ sơ về đúng người đề xuất, không dùng chung với từ chối.
     */
    public function returnRequest(Request $request, LeaveRequest $leaveRequest)
    {
        $user = $request->user();
        $data = $request->validate([
            'decision_note' => 'required|string|min:3|max:2000',
        ]);
        $status = $leaveRequest->status === 'PENDING' ? 'PENDING_COMMANDER' : $leaveRequest->status;

        if ($status === 'PENDING_COMMANDER') {
            abort_unless(LeaveAccess::canCommandRequest($leaveRequest->loadMissing('personnel'), $user), 403, 'Chỉ chỉ huy cơ quan/đơn vị theo cây đơn vị được trả hồ sơ.');
            $action = 'COMMANDER_RETURNED';
            $message = 'Đề xuất nghỉ phép đã được chỉ huy trả lại để bổ sung/chỉnh sửa.';
        } elseif ($status === 'PENDING_AGENCY') {
            abort_unless(LeaveAccess::canHandleAgency((string) $leaveRequest->managing_agency, $user), 403, 'Tài khoản không thuộc cơ quan quản lý của quân nhân này.');
            $action = 'AGENCY_RETURNED';
            $message = 'Đề xuất nghỉ phép đã được cơ quan quản lý trả lại để bổ sung/chỉnh sửa.';
        } else {
            abort_unless($status === 'PENDING_HEAD', 422, 'Đơn không ở bước có thể trả lại.');
            abort_unless(LeaveAccess::canHeadSign($user), 403, 'Chỉ thủ trưởng/Ban Giám hiệu được trả lại hồ sơ.');
            $action = 'HEAD_RETURNED';
            $message = 'Đề xuất nghỉ phép đã được thủ trưởng/Ban Giám hiệu trả lại cơ quan quản lý.';
        }

        $leaveRequest->update([
            'status' => $action === 'HEAD_RETURNED' ? 'PENDING_AGENCY' : 'RETURNED',
            'decision_note' => $data['decision_note'],
            'decided_by_user_id' => $user->id,
            'decided_by_username' => $user->email,
            'decided_at' => now(),
        ]);
        LeaveAuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => 'request',
            'entity_id' => $leaveRequest->id,
            'details' => $data,
        ]);
        if ($action === 'HEAD_RETURNED') {
            $this->notifyManagement($leaveRequest, 'Đơn nghỉ phép #'.$leaveRequest->id.' đã được thủ trưởng/Ban Giám hiệu trả lại. Lý do: '.$data['decision_note']);
        }
        if ($action !== 'HEAD_RETURNED') {
            $this->notifyProposer($leaveRequest, $message);
        }

        return back()->with('success', 'Đã trả lại đề xuất nghỉ phép và lưu rõ lý do.');
    }

    private function notifyManagement(LeaveRequest $leave, ?string $body = null): void
    {
        $agency=(string)($leave->managing_agency ?: LeaveAccess::QUAN_LUC); \App\Models\User::where('status',1)->get()->filter(fn($u)=>LeaveAccess::canHandleAgency($agency,$u))->each(fn($u)=>LeaveAlert::create(['user_id'=>$u->id,'request_id'=>$leave->id,'kind'=>'PENDING_AGENCY','title'=>'Cập nhật xử lý phép','body'=>$body ?: $leave->personnel_name.' đã được chỉ huy cơ quan duyệt chuyển lên cơ quan quản lý.']));
    }

    private function notifyHeadSigners(LeaveRequest $leave): void
    {
        \App\Models\User::where('status',1)->get()->filter(fn($u)=>LeaveAccess::canHeadSign($u))->each(fn($u)=>LeaveAlert::create(['user_id'=>$u->id,'request_id'=>$leave->id,'kind'=>'PENDING_HEAD','title'=>'Đơn nghỉ phép chờ thủ trưởng ký','body'=>$leave->personnel_name.' đã được cơ quan quản lý thẩm định và trình ký.']));
    }

    private function notifyProposer(LeaveRequest $leave,string $body): void
    {
        if ($leave->created_by) LeaveAlert::create(['user_id'=>$leave->created_by,'request_id'=>$leave->id,'kind'=>'LEAVE_DECISION','title'=>'Cập nhật đề xuất nghỉ phép','body'=>$body]);
    }

    private function sendFinalApprovalMail(LeaveRequest $leave): void
    {
        $leave->loadMissing('personnel'); $email=$leave->personnel?->gmail?:($leave->personnel?->email?:$leave->proposer_email);
        if (!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) return;
        $subject='Đề xuất nghỉ phép #'.$leave->id.' đã được duyệt'; $body='Đề xuất nghỉ phép của '.$leave->personnel_name.' đã được cơ quan quản lý duyệt sau khi Ban Giám hiệu ký. Thời gian: '.($leave->from_date?->format('d/m/Y')??'').' - '.($leave->to_date?->format('d/m/Y')??'').'.'; $log=['request_id'=>$leave->id,'to_email'=>$email,'subject'=>$subject,'body'=>$body,'mode'=>config('mail.default'),'kind'=>'FINAL_APPROVAL','ok'=>false];
        try { Mail::raw($body,fn($message)=>$message->to($email)->subject($subject)); $log['ok']=true; } catch(\Throwable $e) { $log['error']=$e->getMessage(); }
        LeaveMailLog::create($log);
    }
}
