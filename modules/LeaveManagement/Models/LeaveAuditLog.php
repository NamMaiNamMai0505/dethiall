<?php

namespace Modules\LeaveManagement\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAuditLog extends Model
{
    protected $table = 'leave_audit_logs';
    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'details'];
    protected $casts = ['details' => 'array'];
    public function user(){return $this->belongsTo(\App\Models\User::class);}
}
