<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model { protected $table='inventory_movements'; protected $fillable=['material_id','type','quantity','reference','note','created_by']; protected $casts=['quantity'=>'integer']; public function material(){return $this->belongsTo(InventoryMaterial::class,'material_id');} public function user(){return $this->belongsTo(\App\Models\User::class,'created_by');} }
