<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryBrokenLog extends Model { protected $table='inventory_broken_logs'; protected $fillable=['event_type','source_type','source_id','asset_id','asset_code','asset_name','quantity','original_grade','grade_after','status_after','reason','result_note','performer','event_at','actor_user_id']; protected $casts=['quantity'=>'decimal:2','event_at'=>'datetime']; public function asset(){return $this->belongsTo(InventoryAsset::class,'asset_id');} public function actor(){return $this->belongsTo(\App\Models\User::class,'actor_user_id');} }
