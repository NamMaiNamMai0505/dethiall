<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class ResearchNorm extends Model
{
    use HasStandardHoursPeriod, SoftDeletes;

    protected $table = 'research_hour_norms';

    protected $fillable = [
        'object_type_id',
        'year',
        'period_mode',
        'research_hours',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'research_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function objectType(): BelongsTo
    {
        return $this->belongsTo(ObjectType::class, 'object_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByYear($query, ?string $year)
    {
        if (blank($year)) {
            return $query;
        }

        return $query->where('year', $year);
    }

    public function scopeByObjectType($query, $objectTypeId)
    {
        if (blank($objectTypeId)) {
            return $query;
        }

        return $query->where('object_type_id', $objectTypeId);
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng';
    }
}
