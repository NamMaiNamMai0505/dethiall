<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryTransfer extends Model { protected $table='inventory_transfers'; protected $fillable=['asset_id','material_id','from_classroom_id','to_classroom_id','type','status','requested_by','decided_by','reason','decision_note','decision_number','decided_at']; protected $casts=['decided_at'=>'datetime']; public function asset(){return $this->belongsTo(InventoryAsset::class,'asset_id');} public function fromClassroom(){return $this->belongsTo(\Modules\Classroom\Models\Classroom::class,'from_classroom_id');} public function toClassroom(){return $this->belongsTo(\Modules\Classroom\Models\Classroom::class,'to_classroom_id');} }
