<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryWarehouse extends Model { protected $table='inventory_warehouses'; protected $fillable=['code','name','location','manager_id','description','active']; protected $casts=['active'=>'boolean']; public function manager(){return $this->belongsTo(\App\Models\User::class,'manager_id');} public function items(){return $this->hasMany(InventoryWarehouseItem::class,'warehouse_id');} }
