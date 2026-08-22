<?php
namespace Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryProposal extends Model { protected $table='inventory_proposals'; protected $fillable=['type','status','title','description','unit_id','created_by','proposed_by_user_id','proposed_by_username','proposed_by_display_name','nganh_code','proposal_code','decided_by','decision_number','decision_note','decision_issuing_level','decision_signer','decided_at','completed_at']; protected $casts=['decided_at'=>'datetime','completed_at'=>'datetime']; public function items(){return $this->hasMany(InventoryProposalItem::class,'proposal_id');} public function unit(){return $this->belongsTo(\Modules\Unit\Models\Unit::class);} }
