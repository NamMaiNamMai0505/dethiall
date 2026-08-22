<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilitaryRank extends Model
{
    protected $fillable = [
        'code', 'name', 'group_key', 'group_name', 'navy_equivalent',
        'abbreviation', 'stars', 'bars', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'stars' => 'integer',
        'bars' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $suffix = $this->navy_equivalent ? ' ('.$this->navy_equivalent.')' : '';
        $mark = $this->abbreviation ? ' · '.$this->abbreviation : '';

        return $this->name.$suffix.$mark;
    }
}
