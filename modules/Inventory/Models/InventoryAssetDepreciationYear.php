<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAssetDepreciationYear extends Model
{
    protected $table = 'inventory_asset_depreciation_years';

    protected $fillable = ['asset_id', 'year', 'depreciation_rate', 'depreciation_amount', 'remaining_value', 'created_by'];

    protected $casts = [
        'year' => 'integer',
        'depreciation_rate' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'remaining_value' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
    }
}
