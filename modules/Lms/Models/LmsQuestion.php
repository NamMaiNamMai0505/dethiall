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
        'lms_question_bank_id', 'lms_lesson_id', 'type', 'stem', 'options', 'correct_answer', 'points', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'float',
    ];

    /** Chỉ hiển thị nội dung câu hỏi, không kèm nhãn đề nguồn như "(Đề 2)". */
    public function getStemAttribute($value): string
    {
        $stem = (string) $value;
        $stem = preg_replace('/^\s*b(?:ài|ai)\s*\d+\s*[:.)-]\s*\d+\s*c(?:âu|au)\s*/iu', '', $stem) ?: $stem;
        $stem = preg_replace('/\s*\(\s*Đề\s*\d+\s*\)\s*$/iu', '', $stem) ?: $stem;
        return trim($stem);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(LmsQuestionBank::class, 'lms_question_bank_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lms_lesson_id');
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

    public function correctAnswerLabel(): string
    {
        if ($this->type !== self::TYPE_MCQ || ! is_array($this->options)) {
            return (string) $this->correct_answer;
        }
        $index = filter_var($this->correct_answer, FILTER_VALIDATE_INT);
        return $index !== false && $index >= 0 ? chr(65 + $index) : (string) $this->correct_answer;
    }
}
