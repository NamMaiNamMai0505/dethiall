<?php
namespace Modules\LeaveManagement\Models;
use App\Models\SystemNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
class LeaveAlert extends Model { protected $table='leave_alerts'; protected $fillable=['user_id','request_id','kind','title','body','read_at']; protected $casts=['read_at'=>'datetime']; protected static function booted(){static::created(function(self $alert):void{if(!Schema::hasTable('system_notifications'))return;SystemNotification::create(['user_id'=>$alert->user_id,'actor_id'=>auth()->id(),'module'=>'leave-management','action'=>strtolower((string)$alert->kind),'type'=>'leave-management','title'=>$alert->title,'message'=>$alert->body,'url'=>$alert->request_id?'/quan-ly-phep/requests/'.$alert->request_id:'/quan-ly-phep/bao-cao','meta'=>['leave_alert_id'=>$alert->id,'leave_request_id'=>$alert->request_id,'kind'=>$alert->kind],'read_at'=>null]);});} public function user(){return $this->belongsTo(\App\Models\User::class);} public function request(){return $this->belongsTo(LeaveRequest::class,'request_id');} }
