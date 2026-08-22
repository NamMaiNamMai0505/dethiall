<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsExam extends Model
{
    use SoftDeletes;

    protected $table = 'lms_exams';

    protected $fillable = [
        'lms_course_id', 'title', 'description', 'duration_minutes', 'opens_at', 'closes_at',
        'max_attempts', 'pass_score', 'shuffle_questions', 'shuffle_options', 'proctor_basic',
        'require_fullscreen', 'auto_submit_on_leave', 'max_blur_events',
        'is_published', 'publish_score_after_submit', 'created_by',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'pass_score' => 'float',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'proctor_basic' => 'boolean',
        'require_fullscreen' => 'boolean',
        'auto_submit_on_leave' => 'boolean',
        'is_published' => 'boolean',
        'publish_score_after_submit' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(LmsQuestion::class, 'lms_exam_questions', 'lms_exam_id', 'lms_question_id')
            ->withPivot(['sort_order', 'points'])
            ->orderByPivot('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LmsExamAttempt::class, 'lms_exam_id');
    }

    public function isOpenNow(): bool
    {
        if (! $this->is_published) {
            return false;
        }
        $now = now();
        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }
        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        return true;
    }
}
