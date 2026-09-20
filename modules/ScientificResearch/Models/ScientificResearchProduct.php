<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchProduct extends Model
{
    use SoftDeletes;

    protected $fillable = ['registration_id', 'type', 'title', 'authors', 'publisher', 'published_year', 'description', 'status', 'created_by'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }
}
