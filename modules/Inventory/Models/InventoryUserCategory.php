<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryUserCategory extends Model { protected $table='inventory_user_categories'; protected $fillable=['user_id','category_id']; public function user(){return $this->belongsTo(\App\Models\User::class);} public function category(){return $this->belongsTo(InventoryCategory::class);} }
