<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveBatch extends Model { protected $table='leave_batches'; protected $fillable=['request_id','personnel_id','personnel_code','personnel_name','object_type','leave_type','batch_index','batch_label','label','start_date','end_date','total_days','note','created_by','created_by_user_id']; protected $casts=['start_date'=>'date','end_date'=>'date']; public function request(){return $this->belongsTo(LeaveRequest::class,'request_id');} public function personnel(){return $this->belongsTo(LeavePersonnel::class,'personnel_id');} }
