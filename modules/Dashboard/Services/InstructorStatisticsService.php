<?php

namespace Modules\Dashboard\Services;

use Modules\Dashboard\Support\DashboardScope;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Unit\Models\Unit;

class InstructorStatisticsService
{
    /**
     * Lấy thống kê theo khoa/giảng viên
     */
    public function getStatistics(
        $unitId = null,
        $instructorId = null,
        $startDate = null,
        $endDate = null,
        ?array $scope = null
    ) {
        // Build query
        $query = ScheduleDetail::with(['instructor.unit', 'trainingSchedule.classModel']);
        if ($scope !== null) {
            DashboardScope::applyToScheduleQuery($query, $scope);
        }

        // Filter theo active training schedules (ngày hiện tại nằm trong khoảng start_date - end_date)
        $query->whereHas('trainingSchedule', function ($q) {
            $q->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());
        });

        // Filter theo unit
        if ($unitId) {
            $query->whereHas('instructor', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            });
        }

        // Filter theo instructor
        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        // Filter theo date range
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // Chỉ lấy tiết thực hành và lý thuyết (không tính tự học và thi)
        $query->whereIn('lesson_type', ['theory', 'practice']);

        $scheduleDetails = $query->get();

        return $this->calculateInstructorStatistics($scheduleDetails);
    }

    /**
     * Lấy khoảng thời gian của schedule details cho instructor
     */
    public function getDateRange($unitId = null, $instructorId = null, ?array $scope = null)
    {
        $query = ScheduleDetail::query();
        if ($scope !== null) {
            DashboardScope::applyToScheduleQuery($query, $scope);
        }

        // Filter theo active training schedules (ngày hiện tại nằm trong khoảng start_date - end_date)
        $query->whereHas('trainingSchedule', function ($q) {
            $q->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());
        });

        // Filter theo unit
        if ($unitId) {
            $query->whereHas('instructor', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            });
        }

        // Filter theo instructor
        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        // Chỉ lấy tiết thực hành và lý thuyết
        $query->whereIn('lesson_type', ['theory', 'practice']);

        $minDate = $query->min('date');
        $maxDate = $query->max('date');

        return [
            'start_date' => $minDate ?? now()->toDateString(),
            'end_date' => $maxDate ?? now()->toDateString(),
        ];
    }

    /**
     * Tính toán thống kê giảng viên
     */
    private function calculateInstructorStatistics($scheduleDetails)
    {
        // Tổng số giảng viên
        $instructorIds = $scheduleDetails->pluck('instructor_id')->unique()->filter();
        $totalInstructors = $instructorIds->count();

        // Tổng số lớp được dạy
        $classIds = $scheduleDetails->pluck('trainingSchedule.class_code')->unique()->filter();
        $totalClasses = $classIds->count();

        // Tổng số tiết đã giảng
        $totalTheoryLessons = $scheduleDetails->where('lesson_type', 'theory')->count();
        $totalPracticeLessons = $scheduleDetails->where('lesson_type', 'practice')->count();
        $totalLessons = $totalTheoryLessons + $totalPracticeLessons;

        // Chi tiết từng giảng viên
        $instructorDetails = [];
        $instructors = Instructor::whereIn('id', $instructorIds)->with('unit')->get();

        foreach ($instructors as $instructor) {
            $instructorSchedules = $scheduleDetails->where('instructor_id', $instructor->id);

            $theoryCount = $instructorSchedules->where('lesson_type', 'theory')->count();
            $practiceCount = $instructorSchedules->where('lesson_type', 'practice')->count();
            $totalCount = $theoryCount + $practiceCount;

            $classes = $instructorSchedules->pluck('trainingSchedule.class_code')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            $classNames = $instructorSchedules->pluck('trainingSchedule.classModel.name')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            $instructorDetails[] = [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'code' => $instructor->code,
                'unit' => optional($instructor->unit)->name ?? 'N/A',
                'theory_lessons' => $theoryCount,
                'practice_lessons' => $practiceCount,
                'total_lessons' => $totalCount,
                'class_count' => count($classes),
                'classes' => $classes,
                'class_names' => $classNames,
            ];
        }

        // Sort by total lessons (nhiều nhất trước)
        usort($instructorDetails, function ($a, $b) {
            return $b['total_lessons'] <=> $a['total_lessons'];
        });

        return [
            'overview' => [
                'total_instructors' => $totalInstructors,
                'total_classes' => $totalClasses,
                'total_lessons' => $totalLessons,
                'theory_lessons' => $totalTheoryLessons,
                'practice_lessons' => $totalPracticeLessons,
            ],
            'instructor_details' => $instructorDetails,
            'chart_data' => $this->prepareChartData($instructorDetails, $totalTheoryLessons, $totalPracticeLessons),
        ];
    }

    /**
     * Chuẩn bị dữ liệu cho charts
     */
    private function prepareChartData($instructorDetails, $totalTheoryLessons, $totalPracticeLessons)
    {
        return [
            // Pie chart: Phân bố lý thuyết vs thực hành
            'lesson_type_pie' => [
                'labels' => ['Lý thuyết', 'Thực hành'],
                'data' => [$totalTheoryLessons, $totalPracticeLessons],
                'colors' => ['#3b82f6', '#10b981'],
            ],
            // Bar chart: Top giảng viên (theo số tiết)
            'instructor_workload_bar' => [
                'labels' => array_slice(array_column($instructorDetails, 'name'), 0, 10),
                'data' => array_slice(array_column($instructorDetails, 'total_lessons'), 0, 10),
            ],
            // Stacked bar: Lý thuyết vs Thực hành theo từng GV
            'instructor_lesson_type_stacked' => [
                'labels' => array_slice(array_column($instructorDetails, 'name'), 0, 10),
                'datasets' => [
                    [
                        'label' => 'Lý thuyết',
                        'data' => array_slice(array_column($instructorDetails, 'theory_lessons'), 0, 10),
                        'backgroundColor' => '#3b82f6',
                    ],
                    [
                        'label' => 'Thực hành',
                        'data' => array_slice(array_column($instructorDetails, 'practice_lessons'), 0, 10),
                        'backgroundColor' => '#10b981',
                    ],
                ],
            ],
        ];
    }
}
