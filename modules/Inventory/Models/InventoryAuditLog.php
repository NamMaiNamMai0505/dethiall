<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryAuditLog extends Model { protected $table='inventory_audit_logs'; protected $fillable=['user_id','action','entity_type','entity_id','details']; protected $casts=['details'=>'array']; public function user(){return $this->belongsTo(\App\Models\User::class);} }
