<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsSurveyTemplateQuestion extends Model
{
    protected $table = 'lms_survey_template_questions';

    protected $fillable = [
        'lms_survey_template_id', 'type', 'stem', 'options', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(LmsSurveyTemplate::class, 'lms_survey_template_id');
    }
}
