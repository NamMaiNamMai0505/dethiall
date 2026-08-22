<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Instructor\Models\Instructor;
use Modules\StandardHours\Models\Concerns\HasStandardHoursPeriod;

class ExternalActivityRecord extends Model
{
    use HasStandardHoursPeriod, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_ADDITIONAL_DUTY = 'additional_duty';

    public const TYPE_ORGANIZATION = 'organization';

    public const TYPE_SOCIAL = 'social';

    public const TYPE_TRAINING = 'training';

    public const TYPE_OTHER = 'other';

    protected $table = 'instructor_external_activity_records';

    protected $fillable = [
        'instructor_id',
        'activity_type',
        'activity_name',
        'activity_details',
        'from_date',
        'to_date',
        'year',
        'period_mode',
        'role_or_position',
        'organizer',
        'location',
        'result',
        'notes',
        'evidence_path',
        'status',
        'review_note',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'year' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
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

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user?->can('standard-hours.override-approved') || $this->isEditable();
    }

    public function getStatusTextAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? 'Không xác định';
    }

    public function getActivityTypeTextAttribute(): string
    {
        return self::activityTypeOptions()[$this->activity_type] ?? 'Khác';
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        if (blank($this->evidence_path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $this->evidence_path), '/');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($nested) use ($term) {
            $nested
                ->where('activity_name', 'like', "%{$term}%")
                ->orWhere('activity_details', 'like', "%{$term}%")
                ->orWhere('organizer', 'like', "%{$term}%")
                ->orWhereHas('instructor', function ($instructorQuery) use ($term) {
                    $instructorQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
        });
    }

    public static function activityTypeOptions(): array
    {
        return [
            self::TYPE_ADDITIONAL_DUTY => 'Công tác kiêm nhiệm',
            self::TYPE_ORGANIZATION => 'Đoàn thể / phong trào',
            self::TYPE_SOCIAL => 'Hoạt động xã hội',
            self::TYPE_TRAINING => 'Bồi dưỡng / tập huấn ngoài kế hoạch',
            self::TYPE_OTHER => 'Hoạt động khác',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_SUBMITTED => 'Đã gửi',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
        ];
    }
}
