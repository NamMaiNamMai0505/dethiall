<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class ConversionRecord extends Model
{
    use HasStandardHoursPeriod, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'instructor_conversion_records';

    protected $fillable = [
        'instructor_id',
        'conversion_category_id',
        'activity_name',
        'activity_date',
        'year',
        'period_mode',
        'quantity',
        'converted_hours',
        'has_other_remuneration',
        'is_external_invitation',
        'notes',
        'evidence_path',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'activity_date' => 'date',
        'quantity' => 'decimal:2',
        'converted_hours' => 'decimal:2',
        'has_other_remuneration' => 'boolean',
        'is_external_invitation' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function conversionCategory(): BelongsTo
    {
        return $this->belongsTo(ConversionCategory::class, 'conversion_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeByInstructor($query, $instructorId)
    {
        if (blank($instructorId)) {
            return $query;
        }

        return $query->where('instructor_id', $instructorId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        if (blank($categoryId)) {
            return $query;
        }

        return $query->where('conversion_category_id', $categoryId);
    }

    public function scopeByStatus($query, ?string $status)
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeByYear($query, ?string $year)
    {
        if (blank($year)) {
            return $query;
        }

        return $query->where('year', $year);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('activity_name', 'like', "%{$term}%")
                ->orWhereHas('instructor', function ($iq) use ($term) {
                    $iq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
        });
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user?->can('standard-hours.override-approved')) {
            return true;
        }

        return $this->isEditable();
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_SUBMITTED => 'Chờ thẩm định',
            self::STATUS_APPROVED => 'Đã thẩm định',
            self::STATUS_REJECTED => 'Cần bổ sung',
            default => 'Không xác định',
        };
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        if (blank($this->evidence_path)) {
            return null;
        }

        // Relative path avoids APP_URL=localhost mismatch with the browser host/port.
        return '/storage/'.ltrim(str_replace('\\', '/', $this->evidence_path), '/');
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_SUBMITTED => 'Chờ thẩm định',
            self::STATUS_APPROVED => 'Đã thẩm định',
            self::STATUS_REJECTED => 'Cần bổ sung',
        ];
    }
}
