<?php

namespace Modules\Dashboard\Services;

use Modules\Dashboard\Support\DashboardScope;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;

class ClassStatisticsService
{
    /**
     * Lấy thống kê theo ngành đào tạo/lớp
     */
    public function getStatistics(
        $specializationId = null,
        $classCode = null,
        $startDate = null,
        $endDate = null,
        ?array $scope = null
    ) {
        // Build query cơ bản
        $query = ScheduleDetail::with(['subject', 'trainingSchedule.classModel', 'trainingSchedule.specialization']);
        if ($scope !== null) {
            DashboardScope::applyToScheduleQuery($query, $scope);
        }

        // Filter theo active training schedules
        $query->whereHas('trainingSchedule', function ($q) use ($specializationId, $classCode) {
            $q->where('is_active', true);

            if ($specializationId) {
                $q->where('specialization_id', $specializationId);
            }

            if ($classCode) {
                $q->where('class_code', $classCode);
            }
        });

        // Filter theo date range
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // Lấy dữ liệu
        $scheduleDetails = $query->get();

        $statistics = $this->calculateClassStatistics($scheduleDetails);

        // Thêm metadata của training schedule
        $trainingSchedule = null;
        if ($scheduleDetails->isNotEmpty()) {
            $trainingSchedule = $scheduleDetails->first()->trainingSchedule;
        }

        $semesters = [
            'semester_1' => 'Học kỳ 1',
            'semester_2' => 'Học kỳ 2',
            'semester_3' => 'Học kỳ 3',
            'semester_4' => 'Học kỳ 4',
            'semester_5' => 'Học kỳ 5',
            'semester_6' => 'Học kỳ 6',
            'summer' => 'Học kỳ hè',
        ];
        $statistics['schedule_metadata'] = $trainingSchedule ? [
            'class_name' => $trainingSchedule->classModel->name ?? null,
            'class_code' => $trainingSchedule->class_code ?? null,
            'specialization_name' => $trainingSchedule->specialization->name ?? null,
            'semester' => $semesters[$trainingSchedule->semester] ?? 'Không xác định',
            'academic_year' => $trainingSchedule->academic_year ?? null,
        ] : null;

        return $statistics;
    }

    /**
     * Lấy khoảng thời gian của schedule details
     */
    public function getDateRange($specializationId = null, $classCode = null, ?array $scope = null)
    {
        $query = ScheduleDetail::query();
        if ($scope !== null) {
            DashboardScope::applyToScheduleQuery($query, $scope);
        }

        // Filter theo active training schedule
        $query->whereHas('trainingSchedule', function ($q) use ($specializationId, $classCode) {
            $q->where('is_active', true);

            if ($specializationId) {
                $q->where('specialization_id', $specializationId);
            }

            if ($classCode) {
                $q->where('class_code', $classCode);
            }
        });

        $minDate = $query->min('date');
        $maxDate = $query->max('date');

        return [
            'start_date' => $minDate ?? now()->toDateString(),
            'end_date' => $maxDate ?? now()->toDateString(),
        ];
    }

    /**
     * Tính toán thống kê
     */
    private function calculateClassStatistics($scheduleDetails)
    {
        // Lấy danh sách môn học unique
        $subjectIds = $scheduleDetails->pluck('subject_id')->unique()->filter();
        $subjects = Subject::whereIn('id', $subjectIds)->get();

        // Tổng số môn học
        $totalSubjects = $subjects->count();

        // Phân loại tiết học theo lesson_type
        $theoryLessons = $scheduleDetails->where('lesson_type', 'theory')->count();
        $practiceLessons = $scheduleDetails->where('lesson_type', 'practice')->count();
        $selfStudyLessons = $scheduleDetails->where('lesson_type', 'self_study')->count();
        $examLessons = $scheduleDetails->where('lesson_type', 'final_exam')->count();

        // Tổng số môn thi
        $examSubjects = $scheduleDetails->where('lesson_type', 'final_exam')
            ->pluck('subject_id')
            ->unique()
            ->count();

        // Tính tiến độ chung
        $totalPlannedLessons = $subjects->sum(function ($subject) {
            return ($subject->theory_hours ?? 0) + ($subject->practice_hours ?? 0) + ($subject->self_study_hours ?? 0);
        });

        $totalCompletedLessons = $theoryLessons + $practiceLessons + $selfStudyLessons;
        $progressPercent = $totalPlannedLessons > 0
            ? round(($totalCompletedLessons / $totalPlannedLessons) * 100, 1)
            : 0;

        // Chi tiết từng môn
        $subjectDetails = [];
        foreach ($subjects as $subject) {
            $subjectSchedules = $scheduleDetails->where('subject_id', $subject->id);

            $theoryDone = $subjectSchedules->where('lesson_type', 'theory')->count();
            $practiceDone = $subjectSchedules->where('lesson_type', 'practice')->count();
            $selfStudyDone = $subjectSchedules->where('lesson_type', 'self_study')->count();

            $totalDone = $theoryDone + $practiceDone + $selfStudyDone;
            $totalPlanned = ($subject->theory_hours ?? 0) + ($subject->practice_hours ?? 0) + ($subject->self_study_hours ?? 0);

            $subjectProgress = $totalPlanned > 0
                ? round(($totalDone / $totalPlanned) * 100, 1)
                : 0;

            $subjectDetails[] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'theory' => [
                    'done' => $theoryDone,
                    'total' => $subject->theory_hours ?? 0,
                    'percent' => ($subject->theory_hours ?? 0) > 0 ? round(($theoryDone / $subject->theory_hours) * 100, 1) : 0,
                ],
                'practice' => [
                    'done' => $practiceDone,
                    'total' => $subject->practice_hours ?? 0,
                    'percent' => ($subject->practice_hours ?? 0) > 0 ? round(($practiceDone / $subject->practice_hours) * 100, 1) : 0,
                ],
                'self_study' => [
                    'done' => $selfStudyDone,
                    'total' => $subject->self_study_hours ?? 0,
                    'percent' => ($subject->self_study_hours ?? 0) > 0 ? round(($selfStudyDone / $subject->self_study_hours) * 100, 1) : 0,
                ],
                'total_done' => $totalDone,
                'total_planned' => $totalPlanned,
                'progress_percent' => $subjectProgress,
            ];
        }

        // Sort by progress
        usort($subjectDetails, function ($a, $b) {
            return $b['progress_percent'] <=> $a['progress_percent'];
        });

        return [
            'overview' => [
                'total_subjects' => $totalSubjects,
                'theory_lessons' => $theoryLessons,
                'practice_lessons' => $practiceLessons,
                'self_study_lessons' => $selfStudyLessons,
                'exam_subjects' => $examSubjects,
                'exam_lessons' => $examLessons,
                'total_completed' => $totalCompletedLessons,
                'total_planned' => $totalPlannedLessons,
                'progress_percent' => $progressPercent,
            ],
            'subject_details' => $subjectDetails,
            'chart_data' => $this->prepareChartData($subjectDetails, $theoryLessons, $practiceLessons, $selfStudyLessons),
        ];
    }

    /**
     * Chuẩn bị dữ liệu cho charts
     */
    private function prepareChartData($subjectDetails, $theoryLessons, $practiceLessons, $selfStudyLessons)
    {
        return [
            // Pie chart: Phân bố loại tiết
            'lesson_type_pie' => [
                'labels' => ['Lý thuyết', 'Thực hành', 'Tự học'],
                'data' => [$theoryLessons, $practiceLessons, $selfStudyLessons],
                'colors' => ['#3b82f6', '#10b981', '#f59e0b'],
            ],
            // Bar chart: Tiến độ từng môn (top 10)
            'subject_progress_bar' => [
                'labels' => array_slice(array_column($subjectDetails, 'name'), 0, 10),
                'data' => array_slice(array_column($subjectDetails, 'progress_percent'), 0, 10),
            ],
            // Stacked bar: Tiết đã học vs chưa học theo môn
            'subject_completion_stacked' => [
                'labels' => array_slice(array_column($subjectDetails, 'name'), 0, 10),
                'datasets' => [
                    [
                        'label' => 'Đã học',
                        'data' => array_slice(array_column($subjectDetails, 'total_done'), 0, 10),
                        'backgroundColor' => '#10b981',
                    ],
                    [
                        'label' => 'Còn lại',
                        'data' => array_map(function ($detail) {
                            return max(0, $detail['total_planned'] - $detail['total_done']);
                        }, array_slice($subjectDetails, 0, 10)),
                        'backgroundColor' => '#e5e7eb',
                    ],
                ],
            ],
        ];
    }
}
