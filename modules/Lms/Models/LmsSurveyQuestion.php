<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsSurveyQuestion extends Model
{
    protected $table = 'lms_survey_questions';

    protected $fillable = [
        'lms_survey_id', 'type', 'stem', 'options', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(LmsSurvey::class, 'lms_survey_id');
    }
}
