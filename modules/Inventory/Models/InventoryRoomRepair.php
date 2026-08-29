<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomRepair extends Model { protected $table='inventory_room_repairs'; protected $guarded=[]; protected $casts=['repair_date'=>'date','cost'=>'decimal:2']; public function asset(){return $this->belongsTo(InventoryAsset::class);} }
