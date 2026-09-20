<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScientificResearchCouncilMember extends Model
{
    protected $fillable = ['council_id', 'user_id', 'full_name', 'role', 'comment', 'score'];

    public function council(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchCouncil::class, 'council_id');
    }
}
