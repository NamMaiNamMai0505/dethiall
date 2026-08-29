<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomBreakReport extends Model { protected $table='inventory_room_break_reports'; protected $guarded=[]; protected $casts=['broken_at'=>'date','quantity'=>'decimal:2']; public function asset(){return $this->belongsTo(InventoryAsset::class);} public function reporter(){return $this->belongsTo(\App\Models\User::class,'reported_by');} }
