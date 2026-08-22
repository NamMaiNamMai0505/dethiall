<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsProgressEvent extends Model
{
    protected $table = 'lms_progress_events';

    protected $fillable = [
        'lms_course_id', 'user_id', 'trackable_type', 'trackable_id',
        'event', 'progress_pct', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'progress_pct' => 'integer',
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
