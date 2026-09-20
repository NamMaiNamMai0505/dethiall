<?php

namespace Modules\ScientificResearch\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchStaffProfile extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'full_name', 'unit_name', 'academic_title', 'degree', 'specialization', 'research_fields', 'scientific_achievements'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
