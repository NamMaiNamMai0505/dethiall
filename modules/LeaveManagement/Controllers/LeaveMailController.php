<?php
namespace Modules\LeaveManagement\Controllers;
use App\Http\Controllers\ModuleBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Modules\LeaveManagement\Models\{LeaveMailLog,LeaveMailSetting};
class LeaveMailController extends ModuleBaseController {
    private function configure():void{$s=LeaveMailSetting::latest()->first();if(!$s||!$s->host)return;Config::set('mail.mailers.smtp.host',$s->host);Config::set('mail.mailers.smtp.port',$s->port?:587);Config::set('mail.mailers.smtp.username',$s->username);Config::set('mail.mailers.smtp.password',$s->password);Config::set('mail.mailers.smtp.encryption',$s->encryption==='null'?null:$s->encryption);if($s->from_address)Config::set('mail.from.address',$s->from_address);if($s->from_name)Config::set('mail.from.name',$s->from_name);}
    public function index(){return view('leave-management::feature',['section'=>'mail','title'=>'Cấu hình email quản lý phép','setting'=>LeaveMailSetting::latest()->first(),'logs'=>LeaveMailLog::latest()->limit(100)->get()]);}
    public function save(Request $r){$d=$r->validate(['host'=>'nullable|string|max:255','port'=>'nullable|integer|min:1|max:65535','username'=>'nullable|email|max:255','password'=>'nullable|string|max:500','from_address'=>'nullable|email|max:255','from_name'=>'nullable|string|max:255','encryption'=>'nullable|in:tls,ssl,null','dev_mode'=>'nullable|boolean']);$d['updated_by']=$r->user()->id;$setting=LeaveMailSetting::latest()->first();if($setting&&blank($d['password']??null))unset($d['password']);LeaveMailSetting::updateOrCreate(['id'=>$setting?->id],$d);return back()->with('success','Đã lưu cấu hình email.');}
    public function test(Request $r){$d=$r->validate(['to'=>'required|email']);$subject='Kiểm tra email quản lý phép';$body='Đây là email kiểm tra cấu hình quản lý phép.';$log=['to_email'=>$d['to'],'subject'=>$subject,'body'=>$body,'kind'=>'TEST','mode'=>config('mail.default'),'ok'=>false];try{Mail::raw($body,fn($m)=>$m->to($d['to'])->subject($subject));$log['ok']=true;$message='Đã gửi email kiểm tra.';}catch(\Throwable $e){$log['error']=$e->getMessage();$message='Gửi email lỗi: '.$e->getMessage();}LeaveMailLog::create($log);return back()->with($log['ok']?'success':'error',$message);}
}
