<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsSurveyResponse extends Model
{
    protected $table = 'lms_survey_responses';

    protected $fillable = [
        'lms_survey_id', 'user_id', 'answers', 'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(LmsSurvey::class, 'lms_survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
