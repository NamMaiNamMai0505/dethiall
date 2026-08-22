<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveAlert extends Model { protected $table='leave_alerts'; protected $fillable=['user_id','request_id','kind','title','body','read_at']; protected $casts=['read_at'=>'datetime']; public function user(){return $this->belongsTo(\App\Models\User::class);} public function request(){return $this->belongsTo(LeaveRequest::class,'request_id');} }
