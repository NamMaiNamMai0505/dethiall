<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchCouncil extends Model
{
    use SoftDeletes;

    protected $fillable = ['registration_id', 'name', 'type', 'meeting_at', 'location', 'decision_number', 'conclusion', 'status', 'created_by'];

    protected $casts = ['meeting_at' => 'datetime'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ScientificResearchCouncilMember::class, 'council_id');
    }
}
