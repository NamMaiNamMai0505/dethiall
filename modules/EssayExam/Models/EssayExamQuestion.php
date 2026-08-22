<?php

namespace Modules\EssayExam\Models;

use Illuminate\Database\Eloquent\Model;

class EssayExamQuestion extends Model
{
    protected $table = 'essay_exam_questions';
    protected $fillable = ['essay_exam_id', 'lms_lesson_id', 'paper_number', 'paper_status', 'question_number', 'question_type', 'content', 'options', 'answer', 'points'];

    protected $casts = ['options' => 'array'];

    /** Chỉ hiển thị nội dung câu hỏi, không kèm nhãn đề nguồn như "(Đề 2)". */
    public function getContentAttribute($value)
    {
        $content = self::repairText($value);
        $content = preg_replace('/^\s*b(?:ài|ai)\s*\d+\s*[:.)-]\s*\d+\s*c(?:âu|au)\s*/iu', '', (string) $content) ?: $content;
        $content = preg_replace('/\s*\(\s*Đề\s*\d+\s*\)\s*$/iu', '', (string) $content) ?: $content;
        return trim((string) $content);
    }
    public function getAnswerAttribute($value) { return self::repairText($value); }
    public function getOptionsAttribute($value)
    {
        $options = is_array($value) ? $value : (json_decode($value ?: '[]', true) ?: []);
        return array_map(fn ($option) => self::repairText($option), $options);
    }

    private static function repairText($value)
    {
        if (!is_string($value) || $value === '') return $value;
        // DOCX đã cung cấp chuỗi UTF-8. Không chạy iconv hoặc tự xóa dấu hỏi
        // trên dữ liệu hợp lệ vì có thể làm mất chữ trong nội dung câu hỏi.
        return str_replace(['\\~', '\\_', '\\-'], [' ', '-', ''], $value);
    }

    public function exam() { return $this->belongsTo(EssayExam::class, 'essay_exam_id'); }
}
