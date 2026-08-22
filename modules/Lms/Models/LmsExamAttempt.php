<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsExamAttempt extends Model
{
    protected $table = 'lms_exam_attempts';

    protected $fillable = [
        'lms_exam_id', 'user_id', 'started_at', 'submitted_at', 'status',
        'question_order', 'answers', 'score', 'max_score', 'proctor_events', 'blur_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'question_order' => 'array',
        'answers' => 'array',
        'proctor_events' => 'array',
        'score' => 'float',
        'max_score' => 'float',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(LmsExam::class, 'lms_exam_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
