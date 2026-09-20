<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchRepositoryDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['registration_id', 'title', 'document_type', 'file_path', 'file_name', 'keywords', 'summary', 'created_by'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }
}
