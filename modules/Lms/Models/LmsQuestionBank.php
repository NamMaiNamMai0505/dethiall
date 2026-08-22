<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsQuestionBank extends Model
{
    protected $table = 'lms_question_banks';

    protected $fillable = ['lms_course_id', 'title', 'description', 'created_by', 'status', 'submitted_at', 'approved_at', 'approved_by'];

    protected $casts = ['submitted_at' => 'datetime', 'approved_at' => 'datetime'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(LmsCourse::class, 'lms_course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LmsQuestion::class, 'lms_question_bank_id')->orderBy('sort_order');
    }
}
