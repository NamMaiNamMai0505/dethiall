<?php

namespace Modules\StandardHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchRecordMember extends Model
{
    protected $fillable = [
        'research_record_id',
        'instructor_id',
        'role',
        'participation_type',
        'contribution_percent',
        'converted_hours',
        'is_declarant',
        'sort_order',
    ];

    protected $casts = [
        'contribution_percent' => 'decimal:2',
        'converted_hours' => 'decimal:2',
        'is_declarant' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function researchRecord(): BelongsTo
    {
        return $this->belongsTo(ResearchRecord::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Instructor\Models\Instructor::class);
    }
}
