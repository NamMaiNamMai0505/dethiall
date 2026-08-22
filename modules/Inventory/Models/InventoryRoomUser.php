<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomUser extends Model { protected $table='inventory_room_users'; protected $fillable=['classroom_id','user_id','role']; public function classroom(){return $this->belongsTo(\Modules\Classroom\Models\Classroom::class);} public function user(){return $this->belongsTo(\App\Models\User::class);} }
