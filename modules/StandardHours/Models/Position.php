<?php

namespace Modules\StandardHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;

    protected $table = 'standard_positions';

    protected $fillable = [
        'name',
        'description',
        'ratio_percent',
        'min_classroom_percent',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ratio_percent' => 'decimal:2',
        'min_classroom_percent' => 'decimal:2',
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

    /** @deprecated legacy hour_norms table */
    public function hourNorms(): HasMany
    {
        return $this->hasMany(HourNorm::class, 'position_id');
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(\Modules\Instructor\Models\Instructor::class, 'position_id');
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
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getFormattedRatioPercentAttribute(): string
    {
        return number_format((float) $this->ratio_percent, 0).'%';
    }

    public function getFormattedMinClassroomPercentAttribute(): string
    {
        return number_format((float) $this->min_classroom_percent, 0).'%';
    }
}
