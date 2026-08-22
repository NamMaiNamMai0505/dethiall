<?php

namespace Modules\Dashboard\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Unit\Models\Unit;

/**
 * Sprint 8 M5 — widget thống kê LMS (chuyên cần / tiến độ) trên Dashboard admin.
 * Read-only, không đụng business logic portal.
 */
class LmsStatisticsService
{
    public function isReady(): bool
    {
        return Schema::hasTable('lms_courses');
    }

    /**
     * @return array{
     *   ready: bool,
     *   courses_total: int,
     *   courses_published: int,
     *   students_enrolled: int,
     *   pending_grades: int,
     *   attendance_pct: float|null,
     *   progress_pct: float|null,
     *   by_unit: list<array{unit:string,courses:int,students:int,attendance_pct:float|null,progress_pct:float|null}>
     * }
     */
    public function overview(
        ?int $unitId = null,
        ?int $instructorId = null,
        ?array $unitIds = null
    ): array {
        if (! $this->isReady()) {
            return [
                'ready' => false,
                'courses_total' => 0,
                'courses_published' => 0,
                'students_enrolled' => 0,
                'pending_grades' => 0,
                'attendance_pct' => null,
                'progress_pct' => null,
                'by_unit' => [],
            ];
        }

        $courseQuery = LmsCourse::query();
        if ($instructorId) {
            $courseQuery->where('instructor_id', $instructorId);
        } elseif ($unitIds !== null) {
            $scopedUnitIds = array_values(array_filter(array_map('intval', $unitIds), fn (int $id) => $id > 0));
            $courseQuery->whereHas('instructor', fn ($query) => $query
                ->whereIn('unit_id', $scopedUnitIds !== [] ? $scopedUnitIds : [-1]));
        } elseif ($unitId) {
            $courseQuery->where(function ($q) use ($unitId) {
                $q->whereHas('instructor', fn ($iq) => $iq->where('unit_id', $unitId))
                    ->orWhereHas('subject', function ($sq) {
                        // Môn có thể không có unit_id — filter qua instructor chính
                        $sq->whereRaw('1=0');
                    });
            });
        }

        $coursesTotal = (clone $courseQuery)->count();
        $coursesPublished = (clone $courseQuery)->where('status', LmsCourse::STATUS_PUBLISHED)->count();
        $courseIds = (clone $courseQuery)->pluck('id');

        $studentsEnrolled = $courseIds->isEmpty()
            ? 0
            : LmsCourseMember::query()
                ->whereIn('lms_course_id', $courseIds)
                ->where('role', LmsCourseMember::ROLE_STUDENT)
                ->distinct('user_id')
                ->count('user_id');

        $pendingGrades = 0;
        if (Schema::hasTable('lms_assignment_submissions') && $courseIds->isNotEmpty()) {
            $pendingGrades = LmsAssignmentSubmission::query()
                ->whereHas('assignment', fn ($q) => $q->whereIn('lms_course_id', $courseIds))
                ->where('status', 'submitted')
                ->count();
        }

        $attendancePct = $this->avgAttendancePct($courseIds->all());
        $progressPct = $this->avgProgressPct($courseIds->all());

        return [
            'ready' => true,
            'courses_total' => $coursesTotal,
            'courses_published' => $coursesPublished,
            'students_enrolled' => $studentsEnrolled,
            'pending_grades' => $pendingGrades,
            'attendance_pct' => $attendancePct,
            'progress_pct' => $progressPct,
            'by_unit' => $this->byUnit($unitIds, $instructorId),
        ];
    }

    /**
     * @param  list<int>  $courseIds
     */
    protected function avgAttendancePct(array $courseIds): ?float
    {
        if ($courseIds === [] || ! Schema::hasTable('lms_attendance_sessions')) {
            return null;
        }

        $sessionIds = LmsAttendanceSession::query()
            ->whereIn('lms_course_id', $courseIds)
            ->pluck('id');
        if ($sessionIds->isEmpty()) {
            return null;
        }

        $total = LmsAttendanceRecord::query()
            ->whereIn('lms_attendance_session_id', $sessionIds)
            ->count();
        if ($total === 0) {
            return null;
        }

        $present = LmsAttendanceRecord::query()
            ->whereIn('lms_attendance_session_id', $sessionIds)
            ->whereIn('status', ['present', 'late', 'excused'])
            ->count();

        return round(($present / $total) * 100, 1);
    }

    /**
     * @param  list<int>  $courseIds
     */
    protected function avgProgressPct(array $courseIds): ?float
    {
        if ($courseIds === [] || ! Schema::hasTable('lms_progress_summaries')) {
            return null;
        }

        $avg = LmsProgressSummary::query()
            ->whereIn('lms_course_id', $courseIds)
            ->avg('overall_pct');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * @return list<array{unit:string,courses:int,students:int,attendance_pct:float|null,progress_pct:float|null}>
     */
    protected function byUnit(?array $unitIds = null, ?int $instructorId = null): array
    {
        if (! Schema::hasTable('units')) {
            return [];
        }

        $unitsQuery = Unit::query()->orderBy('name');
        if ($unitIds !== null) {
            $scopedUnitIds = array_values(array_filter(array_map('intval', $unitIds), fn (int $id) => $id > 0));
            $unitsQuery->whereIn('id', $scopedUnitIds !== [] ? $scopedUnitIds : [-1]);
        } elseif ($instructorId) {
            $unitId = Instructor::query()
                ->whereKey($instructorId)
                ->value('unit_id');
            $unitsQuery->whereKey($unitId ?: -1);
        }

        $units = $unitsQuery->get(['id', 'name', 'code']);
        $rows = [];

        foreach ($units as $unit) {
            $courseIds = LmsCourse::query()
                ->whereHas('instructor', fn ($q) => $q->where('unit_id', $unit->id))
                ->when($instructorId, fn ($query) => $query->where('instructor_id', $instructorId))
                ->pluck('id');
            if ($courseIds->isEmpty()) {
                continue;
            }

            $students = LmsCourseMember::query()
                ->whereIn('lms_course_id', $courseIds)
                ->where('role', LmsCourseMember::ROLE_STUDENT)
                ->distinct('user_id')
                ->count('user_id');

            $rows[] = [
                'unit' => $unit->name.($unit->code ? ' ('.$unit->code.')' : ''),
                'courses' => $courseIds->count(),
                'students' => $students,
                'attendance_pct' => $this->avgAttendancePct($courseIds->all()),
                'progress_pct' => $this->avgProgressPct($courseIds->all()),
            ];
        }

        return $rows;
    }
}
