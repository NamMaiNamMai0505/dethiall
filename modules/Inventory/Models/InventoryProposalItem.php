<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryProposalItem extends Model { protected $table='inventory_proposal_items'; protected $fillable=['proposal_id','material_id','asset_id','source_asset_id','material_code','material_name','name','quantity','unit','category','nganh_code','chuyen_nganh_code','original_grade','original_code','from_classroom_id','from_room_code','from_room_name','location_note','target_room_id','target_room_code','target_room_name','note']; public function material(){return $this->belongsTo(InventoryMaterial::class,'material_id');} public function asset(){return $this->belongsTo(InventoryAsset::class,'asset_id');} }
