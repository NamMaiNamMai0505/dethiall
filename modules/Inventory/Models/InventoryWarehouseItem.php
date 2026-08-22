<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryWarehouseItem extends Model
{
    protected $table = 'inventory_warehouse_items';
    protected $fillable = ['warehouse_id', 'material_id', 'code', 'name', 'unit', 'quantity', 'minimum_quantity', 'note'];
    protected $casts = ['quantity' => 'decimal:2', 'minimum_quantity' => 'decimal:2'];
    public function warehouse() { return $this->belongsTo(InventoryWarehouse::class); }
    public function material() { return $this->belongsTo(InventoryMaterial::class); }
}
