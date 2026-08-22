<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveMailLog extends Model { protected $table='leave_mail_logs'; protected $fillable=['request_id','to_email','subject','body','mode','ok','error','preview_url','kind']; protected $casts=['ok'=>'boolean']; public function request(){return $this->belongsTo(LeaveRequest::class,'request_id');} }
