<?php

namespace Modules\Lms\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsAssignment;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsExam;
use Modules\Lms\Models\LmsExamAttempt;
use Modules\Lms\Models\LmsGradebookRow;
use Modules\Lms\Models\LmsProgressSummary;
use Modules\Lms\Support\LmsSettings;

class LmsGradebookService
{
    public function __construct(protected LmsProgressService $progress) {}

    /**
     * Build live grade matrix for a course (students as rows).
     *
     * @return array{students: Collection, assignments: Collection, exams: Collection, rows: array<int,array>}
     */
    public function matrix(LmsCourse $course, ?int $onlyUserId = null): array
    {
        $students = $course->members()
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->when($onlyUserId, fn ($query) => $query->where('user_id', $onlyUserId))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->values();

        // Also include class students if mapped
        if ($course->class_id) {
            $extra = User::query()
                ->where('class_id', $course->class_id)
                ->where('user_type', 'student')
                ->when($onlyUserId, fn ($query) => $query->whereKey($onlyUserId))
                ->get();
            $students = $students->concat($extra)->unique('id')->values();
        }

        $assignments = LmsAssignment::query()
            ->where('lms_course_id', $course->id)
            ->orderBy('id')
            ->get();
        $exams = LmsExam::query()
            ->where('lms_course_id', $course->id)
            ->orderBy('id')
            ->get();

        $subs = LmsAssignmentSubmission::query()
            ->whereIn('lms_assignment_id', $assignments->pluck('id'))
            ->when($onlyUserId, fn ($query) => $query->where('user_id', $onlyUserId))
            ->get()
            ->groupBy('user_id');
        $attempts = LmsExamAttempt::query()
            ->whereIn('lms_exam_id', $exams->pluck('id'))
            ->when($onlyUserId, fn ($query) => $query->where('user_id', $onlyUserId))
            ->whereIn('status', ['submitted', 'graded'])
            ->orderByDesc('score')
            ->get()
            ->groupBy('user_id');

        $sessions = LmsAttendanceSession::query()
            ->where('lms_course_id', $course->id)
            ->where(function ($query) {
                $query->whereNull('session_date')->orWhereDate('session_date', '<=', today());
            })
            ->pluck('id');
        $attRecords = LmsAttendanceRecord::query()
            ->whereIn('lms_attendance_session_id', $sessions)
            ->when($onlyUserId, fn ($query) => $query->where('user_id', $onlyUserId))
            ->get()
            ->groupBy('user_id');
        $studentIds = $students->pluck('id');
        $progressRows = LmsProgressSummary::query()
            ->where('lms_course_id', $course->id)
            ->whereIn('user_id', $studentIds)
            ->get()
            ->keyBy('user_id');
        $storedRows = LmsGradebookRow::query()
            ->where('lms_course_id', $course->id)
            ->whereIn('user_id', $studentIds)
            ->get()
            ->keyBy('user_id');
        $weights = LmsSettings::gradeWeights();

        $rows = [];
        foreach ($students as $student) {
            $uid = $student->id;
            $aScores = [];
            $aCells = [];
            foreach ($assignments as $a) {
                $sub = ($subs[$uid] ?? collect())->firstWhere('lms_assignment_id', $a->id);
                $score = $sub?->score;
                $aCells[$a->id] = $sub ? [
                    'score' => $score,
                    'status' => $sub->status,
                    'max' => $a->max_score,
                ] : null;
                if ($score !== null) {
                    $aScores[] = ((float) $score / max(0.01, (float) $a->max_score)) * 10;
                }
            }

            $eScores = [];
            $eCells = [];
            foreach ($exams as $exam) {
                $best = ($attempts[$uid] ?? collect())->where('lms_exam_id', $exam->id)->sortByDesc('score')->first();
                $eCells[$exam->id] = $best ? [
                    'score' => $best->score,
                    'max' => $best->max_score,
                ] : null;
                if ($best && $best->max_score > 0) {
                    $eScores[] = ((float) $best->score / (float) $best->max_score) * 10;
                }
            }

            $present = 0;
            $totalSess = $sessions->count();
            foreach (($attRecords[$uid] ?? collect()) as $rec) {
                if (in_array($rec->status, ['present', 'late', 'excused'], true)) {
                    $present++;
                }
            }
            $attPct = $totalSess > 0 ? round(($present / $totalSess) * 100, 1) : null;

            $prog = $progressRows->get($uid);
            $progressPct = $prog?->overall_pct;

            $assignmentAvg = $aScores ? round(array_sum($aScores) / count($aScores), 2) : null;
            $examAvg = $eScores ? round(array_sum($eScores) / count($eScores), 2) : null;

            // Weighted: 40% assignments, 40% exams, 10% attendance, 10% progress (scale 0-10)
            $w = [];
            if ($assignmentAvg !== null && $weights['assignments'] > 0) {
                $w[] = ['v' => $assignmentAvg, 'w' => $weights['assignments']];
            }
            if ($examAvg !== null && $weights['exams'] > 0) {
                $w[] = ['v' => $examAvg, 'w' => $weights['exams']];
            }
            if ($attPct !== null && $weights['attendance'] > 0) {
                $w[] = ['v' => $attPct / 10, 'w' => $weights['attendance']];
            }
            if ($progressPct !== null && $weights['progress'] > 0) {
                $w[] = ['v' => $progressPct / 10, 'w' => $weights['progress']];
            }
            $computed = null;
            if ($w) {
                $tw = array_sum(array_column($w, 'w'));
                $computed = round(array_sum(array_map(fn ($x) => $x['v'] * $x['w'], $w)) / max(0.01, $tw), 2);
            }

            $stored = $storedRows->get($uid);

            $rows[$uid] = [
                'user' => $student,
                'assignment_cells' => $aCells,
                'exam_cells' => $eCells,
                'assignment_avg' => $assignmentAvg,
                'exam_avg' => $examAvg,
                'attendance_pct' => $attPct,
                'attendance_present' => $present,
                'attendance_total' => $totalSess,
                'progress_pct' => $progressPct,
                'computed_score' => $computed,
                'final_score' => $stored?->final_score,
                'letter' => $stored?->letter,
                'note' => $stored?->note,
                'row' => $stored,
            ];
        }

        return compact('students', 'assignments', 'exams', 'rows');
    }

    public function saveOverride(LmsCourse $course, User $student, ?float $finalScore, ?string $note = null): LmsGradebookRow
    {
        $matrix = $this->matrix($course);
        $data = $matrix['rows'][$student->id] ?? null;

        return LmsGradebookRow::query()->updateOrCreate(
            ['lms_course_id' => $course->id, 'user_id' => $student->id],
            [
                'assignment_avg' => $data['assignment_avg'] ?? null,
                'exam_avg' => $data['exam_avg'] ?? null,
                'attendance_pct' => $data['attendance_pct'] ?? null,
                'progress_pct' => $data['progress_pct'] ?? null,
                'computed_score' => $data['computed_score'] ?? null,
                'final_score' => $finalScore,
                'letter' => $this->toLetter($finalScore ?? $data['computed_score'] ?? null),
                'note' => $note,
                'graded_by' => Auth::id(),
                'graded_at' => now(),
            ]
        );
    }

    public function refreshStored(LmsCourse $course): int
    {
        $matrix = $this->matrix($course);
        $n = 0;
        foreach ($matrix['rows'] as $uid => $data) {
            LmsGradebookRow::query()->updateOrCreate(
                ['lms_course_id' => $course->id, 'user_id' => $uid],
                [
                    'assignment_avg' => $data['assignment_avg'],
                    'exam_avg' => $data['exam_avg'],
                    'attendance_pct' => $data['attendance_pct'],
                    'progress_pct' => $data['progress_pct'],
                    'computed_score' => $data['computed_score'],
                    'letter' => $data['final_score'] !== null
                        ? $this->toLetter($data['final_score'])
                        : $this->toLetter($data['computed_score']),
                ]
            );
            $n++;
        }

        return $n;
    }

    public function toLetter(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        // scale 0-10
        return match (true) {
            $score >= 8.5 => 'A',
            $score >= 7.0 => 'B',
            $score >= 5.5 => 'C',
            $score >= 4.0 => 'D',
            default => 'F',
        };
    }
}
