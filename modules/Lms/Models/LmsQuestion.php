<?php

namespace Modules\Lms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LmsQuestion extends Model
{
    use SoftDeletes;

    public const TYPE_MCQ = 'mcq';

    public const TYPE_TF = 'true_false';

    public const TYPE_SHORT = 'short';

    protected $table = 'lms_questions';

    protected $fillable = [
        'lms_question_bank_id', 'type', 'stem', 'options', 'correct_answer', 'points', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'float',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(LmsQuestionBank::class, 'lms_question_bank_id');
    }

    public function isCorrect(?string $answer): bool
    {
        if ($answer === null || $answer === '') {
            return false;
        }
        $correct = trim((string) $this->correct_answer);
        $given = trim((string) $answer);

        if ($this->type === self::TYPE_SHORT) {
            return mb_strtolower($correct) === mb_strtolower($given);
        }

        return $correct === $given;
    }
}
