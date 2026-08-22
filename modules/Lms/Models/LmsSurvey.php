<?php

namespace Modules\Lms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsSurvey extends Model
{
    protected $table = 'lms_surveys';

    protected $fillable = [
        'lms_course_id', 'lms_survey_template_id', 'title', 'description',
        'is_published', 'is_anonymous',
        'opens_at', 'closes_at', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_anonymous' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LmsSurveyQuestion::class, 'lms_survey_id')->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(LmsSurveyResponse::class, 'lms_survey_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        if (! $this->is_published) {
            return false;
        }
        if ($this->opens_at && now()->lt($this->opens_at)) {
            return false;
        }
        if ($this->closes_at && now()->gt($this->closes_at)) {
            return false;
        }

        return true;
    }
}
