<?php

namespace Modules\StandardHours\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConversionCategory extends Model
{
    use SoftDeletes;

    public const METHOD_COEFFICIENT = 'coefficient';

    public const METHOD_FIXED_HOURS = 'fixed_hours';

    protected $table = 'conversion_categories';

    protected $fillable = [
        'code',
        'name',
        'unit',
        'conversion_method',
        'coefficient',
        'fixed_hours',
        'description',
        'entry_source',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'fixed_hours' => 'decimal:2',
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

    public function conversionRecords(): HasMany
    {
        return $this->hasMany(ConversionRecord::class, 'conversion_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManual($query)
    {
        return $query->where('entry_source', 'manual');
    }

    public function isScheduleGenerated(): bool
    {
        return $this->entry_source === 'schedule';
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

    public function scopeByMethod($query, ?string $method)
    {
        if (blank($method)) {
            return $query;
        }

        return $query->where('conversion_method', $method);
    }

    public function usesCoefficient(): bool
    {
        return $this->conversion_method === self::METHOD_COEFFICIENT;
    }

    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng';
    }

    public function getConversionMethodTextAttribute(): string
    {
        return match ($this->conversion_method) {
            self::METHOD_COEFFICIENT => 'Hệ số quy đổi',
            self::METHOD_FIXED_HOURS => 'Số giờ cố định',
            default => 'Không xác định',
        };
    }

    public function getConversionValueTextAttribute(): string
    {
        if ($this->usesCoefficient()) {
            return number_format((float) $this->coefficient, 2);
        }

        return number_format((float) $this->fixed_hours, 2).' giờ';
    }

    public function calculateHours(float $quantity): float
    {
        if ($this->usesCoefficient()) {
            return round($quantity * (float) $this->coefficient, 2);
        }

        return round($quantity * (float) $this->fixed_hours, 2);
    }

    public static function getConversionMethodOptions(): array
    {
        return [
            self::METHOD_COEFFICIENT => 'Hệ số quy đổi',
            self::METHOD_FIXED_HOURS => 'Số giờ cố định',
        ];
    }
}
