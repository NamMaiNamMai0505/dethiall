<?php

namespace Modules\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GraduationSession extends Model
{
    protected $table = 'grade_graduation_sessions';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_PENDING = 'pending_approve';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'academic_year', 'title', 'session_type', 'status', 'sort_order',
        'note', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(GraduationResult::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Nháp',
            self::STATUS_OPEN => 'Đang mở',
            self::STATUS_CALCULATED => 'Đã tính',
            self::STATUS_PENDING => 'Chờ phê duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_LOCKED => 'Đã khóa',
            default => $this->status,
        };
    }
}
