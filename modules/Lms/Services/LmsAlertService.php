<?php

namespace Modules\Lms\Services;

use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsLearningAlert;
use Modules\Lms\Support\LmsSettings;

class LmsAlertService
{
    public function __construct(protected LmsGradebookService $gradebook) {}

    /**
     * Evaluate rules for all students in a course. Returns number of new/updated alerts.
     */
    public function evaluateCourse(LmsCourse $course): int
    {
        $course->loadMissing('subject');
        $studentIds = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->pluck('user_id');

        $count = 0;
        $matrix = $this->gradebook->matrix($course);

        // Sprint 44 / C5 — ngưỡng vắng riêng từng môn (subjects.absence_limit_percent);
        // môn chưa khai thì dùng mức chung. Quyết định đã chốt: % buổi, vắng có
        // phép không tính vào ngưỡng, vượt ngưỡng chỉ cảnh báo (không tự trượt môn).
        $absenceLimitPercent = $course->subject?->absence_limit_percent
            ?? LmsSettings::defaultAbsenceLimitPercent();

        foreach ($studentIds as $uid) {
            $row = $matrix['rows'][$uid] ?? null;
            if (! $row) {
                continue;
            }

            // Low progress
            $pct = (float) ($row['progress_pct'] ?? 0);
            if ($pct > 0 && $pct < 40) {
                $count += $this->upsert($course->id, $uid, 'low_progress', 'warning',
                    'Tiến độ học thấp',
                    "Hoàn thành khoảng {$pct}% nội dung khóa học.",
                    ['progress_pct' => $pct]
                ) ? 1 : 0;
            }

            // Vượt ngưỡng vắng (không tính vắng có phép — attendance_pct đã loại
            // trừ 'excused' khỏi vắng, xem LmsGradebookService::matrix()).
            $totalSess = (int) ($row['attendance_total'] ?? 0);
            $att = $row['attendance_pct'];
            if ($att !== null && $totalSess > 0) {
                $absencePct = round(100 - $att, 1);
                if ($absencePct > $absenceLimitPercent) {
                    $presentCount = (int) ($row['attendance_present'] ?? 0);
                    $absentCount = $totalSess - $presentCount;
                    $count += $this->upsert($course->id, $uid, 'low_attendance', 'critical',
                        'Vượt ngưỡng vắng',
                        "Đã vắng {$absentCount}/{$totalSess} buổi ({$absencePct}%) — vượt ngưỡng {$absenceLimitPercent}% của môn.",
                        [
                            'absent_count' => $absentCount,
                            'total_sessions' => $totalSess,
                            'absence_pct' => $absencePct,
                            'absence_limit_percent' => $absenceLimitPercent,
                        ]
                    ) ? 1 : 0;
                }
            }

            // Missing overdue assignments
            $overdue = LmsAssignment::query()
                ->where('lms_course_id', $course->id)
                ->where('is_published', true)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->where('allow_late', false)
                ->pluck('id');
            if ($overdue->isNotEmpty()) {
                $submitted = LmsAssignmentSubmission::query()
                    ->where('user_id', $uid)
                    ->whereIn('lms_assignment_id', $overdue)
                    ->pluck('lms_assignment_id');
                $missing = $overdue->diff($submitted)->count();
                if ($missing > 0) {
                    $count += $this->upsert($course->id, $uid, 'missing_assignment', 'warning',
                        'Thiếu bài tập quá hạn',
                        "Còn {$missing} bài tập đã quá hạn chưa nộp.",
                        ['missing' => $missing]
                    ) ? 1 : 0;
                }
            }

            // Fail computed
            $score = $row['final_score'] ?? $row['computed_score'];
            if ($score !== null && $score < 4) {
                $count += $this->upsert($course->id, $uid, 'fail_exam', 'critical',
                    'Nguy cơ không đạt',
                    "Điểm tổng hợp hiện tại: {$score}/10.",
                    ['score' => $score]
                ) ? 1 : 0;
            }
        }

        return $count;
    }

    protected function upsert(
        int $courseId,
        int $userId,
        string $type,
        string $severity,
        string $title,
        string $body,
        array $meta = [],
    ): bool {
        $existing = LmsLearningAlert::query()
            ->where('lms_course_id', $courseId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'title' => $title,
                'body' => $body,
                'meta' => $meta,
            ]);

            return false;
        }

        LmsLearningAlert::create([
            'lms_course_id' => $courseId,
            'user_id' => $userId,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'meta' => $meta,
            'is_read' => false,
        ]);

        return true;
    }
}
