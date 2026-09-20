<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registration_id',
        'completed_on',
        'implementation_year',
        'summary',
        'status',
        'submitted_at',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'completed_on' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ScientificResearchFile::class, 'owner_id')
            ->where('owner_type', 'result');
    }
}
