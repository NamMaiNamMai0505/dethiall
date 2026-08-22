<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;

class ObjectType extends Model
{
    use SoftDeletes;

    protected $table = 'standard_object_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'standard_hours',
        'research_hours',
        'administrative_hours',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'standard_hours' => 'decimal:2',
        'research_hours' => 'decimal:2',
        'administrative_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(Instructor::class, 'object_type_id');
    }

    /** @deprecated legacy tables — retained for trash/history only */
    public function hourNorms(): HasMany
    {
        return $this->hasMany(HourNorm::class, 'object_type_id');
    }

    /** @deprecated legacy tables — retained for trash/history only */
    public function researchNorms(): HasMany
    {
        return $this->hasMany(ResearchNorm::class, 'object_type_id');
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

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getLabelAttribute(): string
    {
        $code = $this->code ? $this->code.' — ' : '';

        return $code.$this->name;
    }

    /**
     * Định mức giờ chuẩn theo chức danh: base × ratio%.
     * VD: 380 × 10% = 38.
     */
    public function standardHoursForRatio(float $ratioPercent): float
    {
        return round((float) $this->standard_hours * $ratioPercent / 100, 2);
    }
}
