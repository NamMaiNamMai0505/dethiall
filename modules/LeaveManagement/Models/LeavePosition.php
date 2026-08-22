<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeavePosition extends Model { protected $table='leave_positions'; protected $fillable=['name','sort_order','active']; protected $casts=['active'=>'boolean']; }
