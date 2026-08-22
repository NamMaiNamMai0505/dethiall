<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsGradebookRow extends Model
{
    protected $table = 'lms_gradebook_rows';

    protected $fillable = [
        'lms_course_id', 'user_id',
        'assignment_avg', 'exam_avg', 'attendance_pct', 'progress_pct',
        'computed_score', 'final_score', 'letter', 'note',
        'graded_by', 'graded_at',
    ];

    protected $casts = [
        'assignment_avg' => 'float',
        'exam_avg' => 'float',
        'attendance_pct' => 'float',
        'progress_pct' => 'float',
        'computed_score' => 'float',
        'final_score' => 'float',
        'graded_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function displayScore(): ?float
    {
        return $this->final_score ?? $this->computed_score;
    }
}
