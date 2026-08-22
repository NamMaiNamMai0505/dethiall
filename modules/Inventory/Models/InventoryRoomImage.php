<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRoomImage extends Model { protected $table='inventory_room_images'; protected $fillable=['classroom_id','path','caption','uploaded_by']; public function classroom(){return $this->belongsTo(\Modules\Classroom\Models\Classroom::class);} public function uploader(){return $this->belongsTo(\App\Models\User::class,'uploaded_by');} }
