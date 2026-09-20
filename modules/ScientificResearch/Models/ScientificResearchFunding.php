<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchFunding extends Model
{
    use SoftDeletes;

    protected $fillable = ['registration_id', 'item_name', 'type', 'amount', 'spent_on', 'note', 'status', 'created_by'];

    protected $casts = ['spent_on' => 'date', 'amount' => 'decimal:2'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }
}
