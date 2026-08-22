<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveExtraStandard extends Model { protected $table='leave_extra_standards'; protected $fillable=['code','label','days','sort_order','active']; protected $casts=['active'=>'boolean']; }
