<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRepair extends Model { protected $table='inventory_repairs'; protected $fillable=['asset_id','status','source_type','content','assigned_to','requested_by','cost','result','result_note','performer','opened_at','started_at','completed_at']; protected $casts=['opened_at'=>'date','started_at'=>'date','completed_at'=>'date']; public function asset(){return $this->belongsTo(InventoryAsset::class,'asset_id');} public function assignee(){return $this->belongsTo(\App\Models\User::class,'assigned_to');} }
