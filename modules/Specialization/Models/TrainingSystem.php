<?php

namespace Modules\Specialization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSystem extends Model
{
    protected $table = 'training_systems';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function specializations(): HasMany
    {
        return $this->hasMany(Specialization::class, 'training_system_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
