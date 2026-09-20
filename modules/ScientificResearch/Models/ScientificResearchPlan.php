<?php

namespace Modules\ScientificResearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchPlan extends Model
{
    use SoftDeletes;

    protected $fillable = ['registration_id', 'name', 'school_year', 'plan_year', 'unit_name', 'objectives', 'assigned_tasks', 'status', 'starts_on', 'ends_on', 'created_by'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }
}
