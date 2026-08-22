<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsLearningAlert extends Model
{
    protected $table = 'lms_learning_alerts';

    protected $fillable = [
        'lms_course_id', 'user_id', 'type', 'severity',
        'title', 'body', 'is_read', 'resolved_at', 'meta',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
