<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveRegulation extends Model { protected $table='leave_regulations'; protected $fillable=['leave_type','object_type','min_years','max_years','base_days','label','description','active']; protected $casts=['active'=>'boolean']; }
