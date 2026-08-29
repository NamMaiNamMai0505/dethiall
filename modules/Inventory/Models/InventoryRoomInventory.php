<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomInventory extends Model { protected $table='inventory_room_inventories'; protected $guarded=[]; protected $casts=['inventory_date'=>'date','actual_quantity'=>'decimal:2','book_quantity'=>'decimal:2']; public function asset(){return $this->belongsTo(InventoryAsset::class);} }
