<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveObjectType extends Model { protected $table='leave_object_types'; protected $fillable=['code','name','sort_order','active']; protected $casts=['active'=>'boolean']; }
