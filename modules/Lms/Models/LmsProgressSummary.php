<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsProgressSummary extends Model
{
    protected $table = 'lms_progress_summaries';

    protected $fillable = [
        'lms_course_id', 'user_id',
        'lessons_done', 'lessons_total',
        'materials_done', 'materials_total',
        'assignments_done', 'assignments_total',
        'exams_done', 'exams_total',
        'overall_pct', 'last_activity_at',
    ];

    protected $casts = [
        'overall_pct' => 'float',
        'last_activity_at' => 'datetime',
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
