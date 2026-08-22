<?php

namespace Modules\StandardHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchCategory extends Model
{
    use SoftDeletes;

    protected $table = 'research_categories';

    protected $fillable = [
        'code',
        'name',
        'unit',
        'research_hours',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'research_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function researchRecords(): HasMany
    {
        return $this->hasMany(ResearchRecord::class, 'research_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng';
    }

    public function getFormattedResearchHoursAttribute(): string
    {
        return number_format((float) $this->research_hours, 0).' giờ';
    }
}
