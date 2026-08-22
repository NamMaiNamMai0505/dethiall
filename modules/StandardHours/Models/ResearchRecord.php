<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class ResearchRecord extends Model
{
    use HasStandardHoursPeriod, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const PARTICIPATION_LEAD = 'lead';

    public const PARTICIPATION_MEMBER = 'member';

    protected $table = 'instructor_research_records';

    protected $fillable = [
        'instructor_id',
        'research_category_id',
        'product_name',
        'role',
        'participation_type',
        'publication_date',
        'publication_place',
        'acceptance_date',
        'year',
        'period_mode',
        'member_count',
        'duration_years',
        'annual_product_hours',
        'calculated_hours',
        'contribution_percent',
        'converted_hours',
        'hours_adjustment_note',
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
        'publication_date' => 'date',
        'acceptance_date' => 'date',
        'member_count' => 'integer',
        'duration_years' => 'integer',
        'annual_product_hours' => 'decimal:2',
        'calculated_hours' => 'decimal:2',
        'contribution_percent' => 'decimal:2',
        'converted_hours' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function researchCategory(): BelongsTo
    {
        return $this->belongsTo(ResearchCategory::class, 'research_category_id');
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

    public function members(): HasMany
    {
        return $this->hasMany(ResearchRecordMember::class)->orderBy('sort_order');
    }

    public function scopeByInstructor($query, $instructorId)
    {
        if (blank($instructorId)) {
            return $query;
        }

        return $query->where(function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId)
                ->orWhereHas('members', fn ($mq) => $mq->where('instructor_id', $instructorId));
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        if (blank($categoryId)) {
            return $query;
        }

        return $query->where('research_category_id', $categoryId);
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
            $q->where('product_name', 'like', "%{$term}%")
                ->orWhere('role', 'like', "%{$term}%")
                ->orWhereHas('instructor', function ($iq) use ($term) {
                    $iq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                })
                ->orWhereHas('members.instructor', function ($iq) use ($term) {
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

    public function getParticipationTypeTextAttribute(): string
    {
        return match ($this->participation_type) {
            self::PARTICIPATION_LEAD => 'Chủ nhiệm',
            self::PARTICIPATION_MEMBER => 'Thành viên',
            default => 'Không xác định',
        };
    }

    public function getHasHoursAdjustmentAttribute(): bool
    {
        if ($this->calculated_hours === null) {
            return false;
        }

        return abs((float) $this->converted_hours - (float) $this->calculated_hours) >= 0.01;
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

    public static function getParticipationTypeOptions(): array
    {
        return [
            self::PARTICIPATION_LEAD => 'Chủ nhiệm',
            self::PARTICIPATION_MEMBER => 'Thành viên',
        ];
    }
}
