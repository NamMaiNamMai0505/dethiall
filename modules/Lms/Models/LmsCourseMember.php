<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsCourseMember extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const ROLE_STUDENT = 'student';

    public const ROLE_LECTURER = 'lecturer';

    public const ROLE_ASSISTANT = 'assistant';

    protected $table = 'lms_course_members';

    protected $fillable = [
        'lms_course_id',
        'user_id',
        'role',
        'source',
        'status',
        'joined_at',
        'synced_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'synced_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
