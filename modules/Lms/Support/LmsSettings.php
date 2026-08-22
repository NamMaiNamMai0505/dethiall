<?php

namespace Modules\Lms\Support;

use App\Support\SystemSettings;

final class LmsSettings
{
    public static function courseStatus(): string
    {
        $value = (string) SystemSettings::get('lms', 'default_course_status', 'draft');

        return in_array($value, ['draft', 'published'], true) ? $value : 'draft';
    }

    public static function assignmentMaxScore(): float
    {
        return max(1, min(1000, (float) SystemSettings::get('lms', 'default_assignment_max_score', 10)));
    }

    public static function submissionMaxMegabytes(): int
    {
        return max(1, min(500, (int) SystemSettings::get('lms', 'submission_max_file_mb', 50)));
    }

    public static function allowLateByDefault(): bool
    {
        return (bool) SystemSettings::get('lms', 'allow_late_by_default', false);
    }

    public static function examDurationMinutes(): int
    {
        return max(5, min(480, (int) SystemSettings::get('lms', 'default_exam_duration_minutes', 45)));
    }

    public static function examAttempts(): int
    {
        return max(1, min(20, (int) SystemSettings::get('lms', 'default_exam_attempts', 1)));
    }

    public static function examPassScore(): float
    {
        return max(0, min(1000, (float) SystemSettings::get('lms', 'default_exam_pass_score', 5)));
    }

    public static function shuffleQuestions(): bool
    {
        return (bool) SystemSettings::get('lms', 'shuffle_questions_by_default', true);
    }

    public static function notifyAssignmentGraded(): bool
    {
        return (bool) SystemSettings::get('lms', 'notify_assignment_graded', true);
    }

    /**
     * Ngưỡng vắng mặc định (%) khi môn chưa khai absence_limit_percent riêng.
     * Sprint 44 / C5 — tránh để NULL nghĩa là "không giới hạn" (dễ bỏ sót).
     */
    public static function defaultAbsenceLimitPercent(): int
    {
        return max(0, min(100, (int) SystemSettings::get('lms', 'default_absence_limit_percent', 20)));
    }

    /** @return array{assignments:float,exams:float,attendance:float,progress:float} */
    public static function gradeWeights(): array
    {
        $weights = [
            'assignments' => max(0, (float) SystemSettings::get('lms', 'grade_weight_assignments', 40)),
            'exams' => max(0, (float) SystemSettings::get('lms', 'grade_weight_exams', 40)),
            'attendance' => max(0, (float) SystemSettings::get('lms', 'grade_weight_attendance', 10)),
            'progress' => max(0, (float) SystemSettings::get('lms', 'grade_weight_progress', 10)),
        ];
        $total = array_sum($weights);
        if ($total <= 0) {
            return ['assignments' => 0.4, 'exams' => 0.4, 'attendance' => 0.1, 'progress' => 0.1];
        }

        return array_map(fn (float $weight): float => $weight / $total, $weights);
    }
}
