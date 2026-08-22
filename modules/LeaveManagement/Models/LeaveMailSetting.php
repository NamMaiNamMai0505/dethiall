<?php
namespace Modules\LeaveManagement\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveMailSetting extends Model { protected $table='leave_mail_settings'; protected $fillable=['host','port','username','password','from_address','from_name','encryption','dev_mode','updated_by']; protected $casts=['password'=>'encrypted','dev_mode'=>'boolean']; }
