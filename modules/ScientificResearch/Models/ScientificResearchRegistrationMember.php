<?php

namespace Modules\ScientificResearch\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScientificResearchRegistrationMember extends Model
{
    protected $fillable = [
        'registration_id',
        'user_id',
        'full_name',
        'unit_name',
        'role',
        'contribution_percent',
        'sort_order',
    ];

    protected $casts = [
        'contribution_percent' => 'decimal:2',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchRegistration::class, 'registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
