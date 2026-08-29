<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomReplacement extends Model { protected $table='inventory_room_replacements'; protected $guarded=[]; protected $casts=['replaced_at'=>'date']; }
