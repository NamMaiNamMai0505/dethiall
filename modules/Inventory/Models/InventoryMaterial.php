<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMaterial extends Model
{
    protected $table = 'inventory_materials';
    protected $fillable = ['category_id', 'building_id', 'classroom_id', 'code', 'name', 'unit', 'quantity', 'min_quantity', 'price', 'status', 'manufacture_year', 'usage_year', 'classification', 'asset_status', 'purchase_date', 'expiry_date', 'location', 'note', 'description'];
    protected $casts = ['quantity' => 'decimal:2', 'min_quantity' => 'decimal:2', 'price' => 'decimal:2', 'purchase_date' => 'date', 'expiry_date' => 'date'];
    public function category() { return $this->belongsTo(InventoryCategory::class, 'category_id'); }
    public function building() { return $this->belongsTo(\Modules\Building\Models\Building::class); }
    public function classroom() { return $this->belongsTo(\Modules\Classroom\Models\Classroom::class); }
    public function movements() { return $this->hasMany(InventoryMovement::class, 'material_id')->latest(); }
}
