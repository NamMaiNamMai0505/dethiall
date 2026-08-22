<?php

namespace Modules\Lms\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Models\LmsMaterial;
use Modules\Lms\Models\LmsProgressEvent;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Models\LmsScormPackage;

class LmsProgressService
{
    public function record(
        LmsCourse $course,
        string $trackableType,
        ?int $trackableId,
        string $event = 'view',
        int $progressPct = 0,
        ?array $meta = null,
        ?User $user = null,
    ): LmsProgressEvent {
        $user = $user ?: Auth::user();
        $ev = LmsProgressEvent::create([
            'lms_course_id' => $course->id,
            'user_id' => $user->id,
            'trackable_type' => $trackableType,
            'trackable_id' => $trackableId,
            'event' => $event,
            'progress_pct' => max(0, min(100, $progressPct)),
            'meta' => $meta,
        ]);

        $this->recompute($course, $user);

        return $ev;
    }

    public function recompute(LmsCourse $course, User $user): LmsProgressSummary
    {
        $lessonsTotal = LmsLesson::query()
            ->where('lms_course_id', $course->id)
            ->where('is_published', true)
            ->count();
        $lessonsDone = LmsProgressEvent::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('trackable_type', 'lesson')
            ->whereIn('event', ['view', 'complete'])
            ->distinct('trackable_id')
            ->count('trackable_id');

        $materialsTotal = LmsMaterial::query()
            ->where('lms_course_id', $course->id)
            ->where('is_published', true)
            ->count()
            + LmsScormPackage::query()
                ->where('lms_course_id', $course->id)
                ->where('is_published', true)
                ->count();

        $matDone = LmsProgressEvent::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereIn('trackable_type', ['material', 'scorm'])
            ->whereIn('event', ['view', 'complete'])
            ->get()
            ->unique(fn ($e) => $e->trackable_type.':'.$e->trackable_id)
            ->count();

        $assignmentsTotal = LmsAssignment::query()
            ->where('lms_course_id', $course->id)
            ->where('is_published', true)
            ->count();
        $assignmentsDone = LmsAssignmentSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_assignment_id', LmsAssignment::query()
                ->where('lms_course_id', $course->id)
                ->pluck('id'))
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        $examsTotal = LmsExam::query()
            ->where('lms_course_id', $course->id)
            ->where('is_published', true)
            ->count();
        $examsDone = LmsExamAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('lms_exam_id', LmsExam::query()->where('lms_course_id', $course->id)->pluck('id'))
            ->whereIn('status', ['submitted', 'graded'])
            ->distinct('lms_exam_id')
            ->count('lms_exam_id');

        $parts = [];
        if ($lessonsTotal > 0) {
            $parts[] = min(1, $lessonsDone / $lessonsTotal) * 100;
        }
        if ($materialsTotal > 0) {
            $parts[] = min(1, $matDone / $materialsTotal) * 100;
        }
        if ($assignmentsTotal > 0) {
            $parts[] = min(1, $assignmentsDone / $assignmentsTotal) * 100;
        }
        if ($examsTotal > 0) {
            $parts[] = min(1, $examsDone / $examsTotal) * 100;
        }
        $overall = $parts ? round(array_sum($parts) / count($parts), 2) : 0.0;

        return LmsProgressSummary::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'user_id' => $user->id],
            [
                'lessons_done' => $lessonsDone,
                'lessons_total' => $lessonsTotal,
                'materials_done' => $matDone,
                'materials_total' => $materialsTotal,
                'assignments_done' => $assignmentsDone,
                'assignments_total' => $assignmentsTotal,
                'exams_done' => $examsDone,
                'exams_total' => $examsTotal,
                'overall_pct' => $overall,
                'last_activity_at' => now(),
            ]
        );
    }

    public function summaryFor(LmsCourse $course, User $user): LmsProgressSummary
    {
        $row = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        return $row ?: $this->recompute($course, $user);
    }
}
