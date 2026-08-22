<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveClass extends Model { protected $table='leave_classes'; protected $fillable=['unit_id','name','active']; protected $casts=['active'=>'boolean']; public function unit(){return $this->belongsTo(\Modules\Unit\Models\Unit::class,'unit_id');} public function personnel(){return $this->hasMany(LeavePersonnel::class,'class_id');} }
