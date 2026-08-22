<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryCategory extends Model { protected $table='inventory_categories'; protected $fillable=['parent_id','code','name','description','active']; public function materials(){return $this->hasMany(InventoryMaterial::class,'category_id');} public function parent(){return $this->belongsTo(self::class,'parent_id');} public function children(){return $this->hasMany(self::class,'parent_id');} public function userAssignments(){return $this->hasMany(InventoryUserCategory::class,'category_id');} }
