<?php

namespace Modules\TrainingSchedule\Services;

use App\Models\User;
use App\Support\AcademicYearCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Class\Models\ClassModel;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Specialization\Models\Specialization;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class TrainingScheduleService
{
    /**
     * Filter and get training schedules query
     */
    public function getFilteredSchedules(Request $request, $withPagination = false)
    {
        $query = TrainingSchedule::with(['specialization', 'creator', 'updater', 'classroom', 'classModel']);

        // Mặc định: tất cả lịch. Chỉ áp filter khi request có giá trị thực (khác rỗng).
        $has = function (string $key) use ($request): bool {
            if (! $request->has($key)) {
                return false;
            }
            $value = $request->input($key);

            return ! ($value === null || $value === '' || (is_array($value) && $value === []));
        };

        if ($has('specialization_id')) {
            $query->bySpecialization($request->input('specialization_id'));
        }

        if ($has('academic_year')) {
            $query->byAcademicYear($request->input('academic_year'));
        }

        if ($has('semester')) {
            $query->bySemester($request->input('semester'));
        }

        if ($has('search')) {
            $query->search(trim((string) $request->input('search')));
        }

        if ($has('class_id')) {
            $classId = $request->input('class_id');
            $class = ClassModel::find($classId);
            if ($class) {
                $query->where(function ($q) use ($classId, $class) {
                    $q->where('class_id', $classId)
                        ->orWhere('class_code', $class->code);
                });
            } else {
                $query->where('class_id', $classId);
            }
        }

        if ($has('is_active')) {
            $status = $request->input('is_active');
            if ($status === 'active' || $status === '1' || $status === 1 || $status === true) {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0' || $status === 0 || $status === false) {
                $query->where('is_active', false);
            }
        }

        $query->orderByDesc('is_active')->orderBy('start_date');

        return $withPagination ? $query : $query->get();
    }

    /**
     * Transform schedules to calendar events
     */
    public function transformToCalendarEvents($schedules, Request $request)
    {
        $events = collect();
        $selectedMonth = $request->get('month');

        foreach ($schedules as $schedule) {
            if (! $schedule->weekly_schedule || ! is_array($schedule->weekly_schedule)) {
                continue;
            }

            foreach ($schedule->weekly_schedule as $weekIndex => $week) {
                if (! $this->isValidWeek($week)) {
                    continue;
                }

                // Apply month filter
                if ($selectedMonth && ! $this->weekMatchesMonth($week, $selectedMonth)) {
                    continue;
                }

                $events->push($this->createEventData($schedule, $weekIndex, $week));
            }
        }

        return $events;
    }

    /**
     * Generate unique code from name
     */
    public function generateUniqueCode($name)
    {
        $baseCode = Str::upper(Str::slug($name, ''));
        $baseCode = substr($baseCode, 0, 10);

        $code = $baseCode;
        $counter = 1;

        while (TrainingSchedule::where('code', $code)->exists()) {
            $code = $baseCode.$counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Process weekly schedule data
     */
    public function processWeeklySchedule(array $weeks)
    {
        $weeklySchedule = [];

        foreach ($weeks as $weekNum => $weekData) {
            $weekInfo = array_filter([
                'start_date' => $weekData['start_date'] ?? null,
                'end_date' => $weekData['end_date'] ?? null,
                'content' => $weekData['content'] ?? null,
            ]);

            // Process daily schedule
            if (! empty($weekData['days'])) {
                $dailySchedule = [];
                foreach ($weekData['days'] as $dayIndex => $dayData) {
                    $dayInfo = array_filter([
                        'date' => $dayData['date'] ?? null,
                    ]);

                    // Process periods for each day
                    if (! empty($dayData['periods'])) {
                        $periods = [];
                        foreach ($dayData['periods'] as $periodIndex => $periodData) {
                            $period = array_filter([
                                'subject_id' => $periodData['subject_id'] ?? null,
                                'instructor_id' => $periodData['instructor_id'] ?? null,
                                'classroom_id' => $periodData['classroom_id'] ?? null,
                                'period' => $periodData['period'] ?? null,
                            ]);
                            if (! empty($period)) {
                                $periods[] = $period;
                            }
                        }
                        if (! empty($periods)) {
                            $dayInfo['periods'] = $periods;
                        }
                    }

                    if (! empty($dayInfo)) {
                        $dailySchedule[$dayIndex] = $dayInfo;
                    }
                }

                if (! empty($dailySchedule)) {
                    $weekInfo['days'] = $dailySchedule;
                }
            }

            if (! empty($weekInfo)) {
                $weeklySchedule[$weekNum] = $weekInfo;
            }
        }

        return $weeklySchedule;
    }

    /**
     * Get filter options
     */
    public function getFilterOptions()
    {
        return [
            'specializations' => Specialization::active()
                ->with('trainingSystem:id,name')
                ->select('id', 'name', 'major_code', 'level', 'training_form', 'training_system_id')
                ->orderBy('name')
                ->get(),
            'academic_years' => $this->getAcademicYears(),
            'semesters' => [
                'semester_1' => 'Học kỳ 1',
                'semester_2' => 'Học kỳ 2',
                'summer' => 'Học kỳ hè',
            ],
        ];
    }

    /**
     * Get available academic years
     */
    public function getAcademicYears()
    {
        return AcademicYearCatalog::options();
    }

    /**
     * Get specialization code
     */
    public function getSpecializationCode(?Specialization $specialization): string
    {
        return trim((string) ($specialization?->major_code ?: $specialization?->code));
    }

    /**
     * Get CSS color class for events
     */
    public function getEventColorClass($specializationName)
    {
        $name = strtolower($specializationName);

        if (strpos($name, 'công nghệ') !== false || strpos($name, 'cntt') !== false) {
            return 'event-cntt';
        } elseif (strpos($name, 'quản trị') !== false || strpos($name, 'qtkd') !== false) {
            return 'event-qtkd';
        } elseif (strpos($name, 'kế toán') !== false) {
            return 'event-kt';
        } elseif (strpos($name, 'marketing') !== false) {
            return 'event-mkt';
        } elseif (strpos($name, 'thiết kế') !== false || strpos($name, 'đồ họa') !== false) {
            return 'event-tkdh';
        } else {
            return 'event-cnmlc';
        }
    }

    /**
     * Check if week data is valid
     */
    private function isValidWeek($week)
    {
        return isset($week['start_date']) && isset($week['end_date']) &&
               ! empty($week['start_date']) && ! empty($week['end_date']);
    }

    /**
     * Check if week matches selected month
     */
    private function weekMatchesMonth($week, $selectedMonth)
    {
        $eventStartDate = new \DateTime($week['start_date']);
        $eventMonth = $eventStartDate->format('n');

        return $eventMonth == $selectedMonth;
    }

    /**
     * Create event data for calendar
     */
    private function createEventData($schedule, $weekIndex, $week)
    {
        $weekNumber = substr($weekIndex, strrpos($weekIndex, '_') + 1);
        $eventStartDate = new \DateTime($week['start_date']);

        return [
            'id' => $schedule->id.'_week_'.$weekIndex,
            'title' => $this->generateEventTitle($schedule, $weekNumber),
            'start' => $week['start_date'],
            'end' => $week['end_date'],
            'backgroundColor' => $schedule->is_active ? '#3b82f6' : '#9ca3af',
            'borderColor' => $schedule->is_active ? '#3b82f6' : '#9ca3af',
            'url' => route('training-schedules.show', $schedule),
            'extendedProps' => [
                'schedule_id' => $schedule->id,
                'specialization' => $schedule->specialization->name ?? 'N/A',
                'specialization_code' => $this->getSpecializationCode($schedule->specialization),
                'content' => $week['content'] ?? '',
                'location' => $this->extractLocation($week),
                'classroom' => $schedule->classroom->name ?? null,
                'classroom_id' => $schedule->classroom_id,
                'week' => $weekNumber,
                'status' => $schedule->is_active ? 'Đang hoạt động' : 'Tạm dừng',
                'academic_year' => $schedule->academic_year,
                'semester' => $schedule->semester_text,
                'class_id' => $schedule->class_id,
                'daily_details' => $week['days'] ?? [],
                'color_class' => $this->getEventColorClass($schedule->specialization->name ?? ''),
                'event_start_month' => $eventStartDate->format('n'),
                'event_start_year' => $eventStartDate->format('Y'),
            ],
        ];
    }

    /**
     * Generate event title for calendar
     */
    private function generateEventTitle($schedule, $weekNumber)
    {
        $code = $this->getSpecializationCode($schedule->specialization);

        return $code.' Tiết (1-2) Sáng';
    }

    /**
     * Extract location from week data
     */
    private function extractLocation($week)
    {
        if (isset($week['days']) && is_array($week['days'])) {
            foreach ($week['days'] as $day) {
                if (isset($day['location']) && ! empty($day['location'])) {
                    return $day['location'];
                }
            }
        }

        return '';
    }

    /**
     * Build weekly schedule from periods (nhiều tiết/ngày)
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  array  $periods  (key: YYYY-MM-DD, value: array các tiết)
     * @return array
     */
    public function buildWeeklyScheduleFromPeriods($startDate, $endDate, $periods)
    {
        $weeklySchedule = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $current = clone $start;
        while ($current <= $end) {
            // Lấy ngày đầu tuần (thứ 2)
            $monday = clone $current;
            if ($monday->format('N') != 1) {
                $monday->modify('last monday');
            }
            $sunday = clone $monday;
            $sunday->modify('+6 days');
            if ($sunday > $end) {
                $sunday = clone $end;
            }
            $week = [
                'start_date' => $monday->format('Y-m-d'),
                'end_date' => $sunday->format('Y-m-d'),
                'days' => [],
            ];
            $day = clone $monday;
            while ($day <= $sunday && $day <= $end) {
                $dateStr = $day->format('Y-m-d');
                $dayPeriods = [];
                if (isset($periods[$dateStr]) && is_array($periods[$dateStr])) {
                    foreach ($periods[$dateStr] as $period) {
                        $lessons = isset($period['lessons']) && trim($period['lessons']) !== '' ? (int) $period['lessons'] : 1;
                        $dayPeriods[] = [
                            'subject_id' => $period['subject_id'] ?? null,
                            'instructor_id' => $period['instructor_id'] ?? null,
                            'classroom_id' => $period['classroom_id'] ?? null,
                            'abbreviation' => $period['abbreviation'] ?? null,
                            'lessons' => $lessons,
                        ];
                    }
                }
                $week['days'][] = [
                    'date' => $dateStr,
                    'periods' => $dayPeriods,
                ];
                $day->modify('+1 day');
            }
            $weeklySchedule[] = $week;
            $current = (clone $sunday)->modify('+1 day');
        }

        return $weeklySchedule;
    }

    /**
     * Build periods 1-9 cho 1 lớp + 1 ngày
     *
     * @param  Collection  $details
     * @return array
     */
    public function buildPeriodsForClassDay($details)
    {
        $result = [];
        for ($i = 1; $i <= 9; $i++) {
            $detail = $details->firstWhere('period', $i);
            $result[$i] = $this->formatPeriodDetail($detail);
        }

        return $result;
    }

    /**
     * Build periods cho 1 lớp + nhiều ngày (group theo ngày, mỗi ngày đủ 9 periods)
     *
     * @param  Collection  $details
     * @param  string  $start
     * @param  string  $end
     * @return array
     */
    public function buildPeriodsForClassRange($details, $start, $end)
    {
        $result = [];
        $dates = $this->getDateRange($start, $end);
        foreach ($dates as $date) {
            $dayDetails = $details->where('date', $date);
            $periods = [];
            for ($i = 1; $i <= 9; $i++) {
                $detail = $dayDetails->firstWhere('period', $i);
                $periods[$i] = $this->formatPeriodDetail($detail);
            }
            $result[] = [
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('vi')->isoFormat('dddd'),
                'periods' => $periods,
            ];
        }

        return $result;
    }

    /**
     * Build periods cho ngành đào tạo + 1 ngày (group theo lớp, mỗi lớp đủ 9 periods)
     *
     * @param  Collection  $details
     * @return array
     */
    public function buildPeriodsForSpecDay($details)
    {
        $result = [];
        $grouped = $details->groupBy(function ($d) {
            return $d->trainingSchedule->classModel->name ?? $d->trainingSchedule->class_code;
        });
        foreach ($grouped as $className => $classDetails) {
            $periods = [];
            for ($i = 1; $i <= 9; $i++) {
                $detail = $classDetails->firstWhere('period', $i);
                $periods[$i] = $this->formatPeriodDetail($detail);
            }
            $result[] = [
                'class_name' => $className,
                'periods' => $periods,
            ];
        }

        return $result;
    }

    /**
     * Helper: build mảng ngày từ start đến end (Y-m-d)
     */
    public function getDateRange($start, $end)
    {
        $dates = [];
        $current = Carbon::parse($start);
        $end = Carbon::parse($end);
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Helper: chuẩn hóa dữ liệu 1 tiết học cho UI (subject_color, lesson_type_icon...)
     */
    public function formatPeriodDetail($detail)
    {
        if (! $detail) {
            return [
                'subject_code' => null,
                'subject_name' => null,
                'subject_color' => null,
                'lesson_type' => null,
                'lesson_type_icon' => null,
                'instructor_name' => null,
                'classroom_name' => null,
            ];
        }
        $lessonTypeIcons = [
            'theory' => '📘',
            'practice' => '🔬',
            'self_study' => '📝',
            'final_exam' => '🏆',
        ];
        $subjectColors = [
            'theory' => '#3B82F6',
            'practice' => '#10B981',
            'self_study' => '#F59E42',
            'final_exam' => '#F43F5E',
        ];
        // $lessonType = $detail->lesson_type;
        $typeMap = [
            'theory' => 'Lý thuyết',
            'practice' => 'Thực hành',
            'self_study' => 'Tự học',
        ];
        $lessonType = $typeMap[$detail->lesson_type] ?? 'Chưa xác định';
        $subject = $detail->subject;

        return [
            'subject_code' => $subject->display_code ?? ($subject->code ?? null),
            'subject_name' => $subject->name ?? null,
            // Ưu tiên viết tắt khi hiển thị / xuất lịch
            'subject_abbreviation' => $subject->abbreviation ?? null,
            'subject_short' => $subject
                ? ($subject->short_label ?: ($subject->name ?? null))
                : null,
            'subject_color' => $subjectColors[$lessonType] ?? '#6B7280',
            'lesson_type' => $lessonType,
            'lesson_type_icon' => $lessonTypeIcons[$detail->lesson_type] ?? '',
            'instructor_name' => $detail->instructor->name ?? null,
            'classroom_name' => $detail->classroom->name ?? null,
        ];
    }

    /**
     * Get schedule details for export to Excel
     * Exports ALL dates in the range, including days with no schedule details
     */
    public function getScheduleDetailsForExport(array $trainingScheduleIds, string $startDate, string $endDate)
    {
        // 1. Get all training schedules with their classes
        $trainingSchedules = TrainingSchedule::with('classModel')
            ->whereIn('id', $trainingScheduleIds)
            ->get();

        // 2. Build map: training_schedule_id => className
        $classesMap = [];
        foreach ($trainingSchedules as $ts) {
            $className = $ts->classModel->name ?? $ts->class_code ?? 'N/A';
            $classesMap[$ts->id] = $className;
        }

        // 3. Generate ALL dates in the range
        $allDates = $this->getDateRange($startDate, $endDate);

        // 4. Get schedule details within date range
        $details = ScheduleDetail::with([
            'trainingSchedule.classModel',
            'subject',
            'instructor',
            'classroom',
        ])
            ->whereIn('training_schedule_id', $trainingScheduleIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // 5. Index details by date|trainingScheduleId|period for O(1) lookup
        $detailsIndex = [];
        foreach ($details as $detail) {
            $key = $detail->date.'|'.$detail->training_schedule_id.'|'.$detail->period;
            $detailsIndex[$key] = $detail;
        }

        // 6. Build result: for EVERY date + EVERY class, create a row
        $result = [];

        foreach ($allDates as $date) {
            foreach ($classesMap as $tsId => $className) {
                // Initialize row with "Trống lịch" for all 9 periods
                $row = [
                    'date' => Carbon::parse($date)->format('d/m/Y').' - '.Carbon::parse($date)->locale('vi')->isoFormat('dddd'),
                    'class_name' => $className,
                    'period_1' => 'Trống lịch',
                    'period_2' => 'Trống lịch',
                    'period_3' => 'Trống lịch',
                    'period_4' => 'Trống lịch',
                    'period_5' => 'Trống lịch',
                    'period_6' => 'Trống lịch',
                    'period_7' => 'Trống lịch',
                    'period_8' => 'Trống lịch',
                    'period_9' => 'Trống lịch',
                ];

                // Fill in periods that have actual schedule data
                for ($period = 1; $period <= 9; $period++) {
                    $key = $date.'|'.$tsId.'|'.$period;

                    if (isset($detailsIndex[$key])) {
                        $detail = $detailsIndex[$key];
                        $periodKey = 'period_'.$period;

                        $subject = $detail->getRelationValue('subject');
                        $instructor = $detail->getRelationValue('instructor');
                        $classroom = $detail->getRelationValue('classroom');
                        $lessonTypeKey = $detail->getAttribute('lesson_type');

                        if ($subject) {
                            $lessonType = $this->getLessonTypeAbbreviation($lessonTypeKey);
                            // Xuất lịch: ưu tiên viết tắt môn (vd TTT → Thuốc thông thường), rồi mã, rồi tên đầy đủ
                            $subjectLabel = $subject->short_label ?: (string) ($subject->name ?? 'N/A');
                            $line1 = $subjectLabel.' - '.$lessonType;
                            $line2 = ($instructor->name ?? 'N/A').' - '.($classroom->name ?? 'N/A');
                            $row[$periodKey] = $line1."\n".$line2;
                        } else {
                            $row[$periodKey] = '';
                        }
                    }
                }

                $result[] = $row;
            }
        }

        return collect($result);
    }

    /**
     * Get lesson type abbreviation for export
     */
    private function getLessonTypeAbbreviation($lessonType)
    {
        $abbreviations = [
            'theory' => 'LT',      // Lý thuyết
            'practice' => 'TH',    // Thực hành
            'self_study' => 'Ôn',  // Tự học
            'final_exam' => 'Thi/Kt', // Thi
        ];

        return $abbreviations[$lessonType] ?? 'LT';
    }

    /**
     * Statistics for calendar "Tổng quan" tab.
     *
     * @return array{kpis: array, charts: array, meta: array}
     */
    public function getCalendarOverviewStatistics(): array
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $nextWeek = $today->copy()->addDays(7);

        $activeSchedules = TrainingSchedule::query()
            ->with(['specialization.trainingSystem:id,name', 'classModel:id,code,name'])
            ->active()
            ->get();

        $totalCourses = $activeSchedules->count();

        $activeClasses = $activeSchedules->filter(function (TrainingSchedule $schedule) use ($today) {
            if (! $schedule->start_date || ! $schedule->end_date) {
                return false;
            }

            return $today->between($schedule->start_date->copy()->startOfDay(), $schedule->end_date->copy()->endOfDay());
        })->count();

        $completedSchedules = $activeSchedules->filter(function (TrainingSchedule $schedule) use ($today) {
            return $schedule->end_date && $schedule->end_date->lt($today);
        })->count();

        $schedulesWithEnd = $activeSchedules->filter(fn (TrainingSchedule $s) => (bool) $s->end_date)->count();
        $completionRate = $schedulesWithEnd > 0
            ? round(($completedSchedules / $schedulesWithEnd) * 100, 1)
            : 0.0;

        $upcomingClasses = $activeSchedules->filter(function (TrainingSchedule $schedule) use ($today, $nextWeek) {
            if (! $schedule->start_date) {
                return false;
            }

            return $schedule->start_date->gt($today) && $schedule->start_date->lte($nextWeek);
        })->count();

        $totalStudents = 0;
        if (class_exists(User::class)) {
            $totalStudents = (int) User::query()
                ->where('user_type', 'student')
                ->count();
        }

        $weekDetails = ScheduleDetail::query()
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['date', 'lesson_type']);

        $sessionsThisWeek = $weekDetails->count();

        // Sessions by day (Mon-Sun)
        $dayLabels = [];
        $dayValues = [];
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $dayLabels[] = $d->locale('vi')->translatedFormat('D d/m');
            $dayValues[] = $weekDetails->filter(function ($item) use ($d) {
                return Carbon::parse($item->date)->isSameDay($d);
            })->count();
        }

        // Lesson type distribution this week
        $lessonTypeMap = [
            'theory' => 'Lý thuyết',
            'practice' => 'Thực hành',
            'self_study' => 'Tự học',
            'final_exam' => 'Thi/kiểm tra',
        ];
        $lessonTypeCounts = [];
        foreach ($lessonTypeMap as $key => $label) {
            $lessonTypeCounts[$label] = $weekDetails->where('lesson_type', $key)->count();
        }
        // include unknown types if any
        foreach ($weekDetails->pluck('lesson_type')->unique()->filter() as $type) {
            if (! isset($lessonTypeMap[$type])) {
                $label = (string) $type;
                $lessonTypeCounts[$label] = ($lessonTypeCounts[$label] ?? 0) + $weekDetails->where('lesson_type', $type)->count();
            }
        }

        // Class status distribution
        $ongoing = $activeClasses;
        $upcoming = $activeSchedules->filter(function (TrainingSchedule $schedule) use ($today) {
            return $schedule->start_date && $schedule->start_date->gt($today);
        })->count();
        $finished = $completedSchedules;
        $notStartedOrOther = max(0, $totalCourses - $ongoing - $upcoming - $finished);

        // Courses by specialization
        $bySpec = $activeSchedules
            ->groupBy(fn (TrainingSchedule $s) => $s->specialization?->selection_label ?? 'Chưa gán ngành')
            ->map->count()
            ->sortDesc();

        return [
            'kpis' => [
                'total_courses' => $totalCourses,
                'total_students' => $totalStudents,
                'active_classes' => $activeClasses,
                'completion_rate' => $completionRate,
                'sessions_this_week' => $sessionsThisWeek,
                'upcoming_classes' => $upcomingClasses,
            ],
            'charts' => [
                'class_status' => [
                    'labels' => ['Đang diễn ra', 'Sắp khai giảng', 'Đã kết thúc', 'Khác'],
                    'values' => [$ongoing, $upcoming, $finished, $notStartedOrOther],
                ],
                'sessions_by_day' => [
                    'labels' => $dayLabels,
                    'values' => $dayValues,
                ],
                'courses_by_specialization' => [
                    'labels' => $bySpec->keys()->values()->all(),
                    'values' => $bySpec->values()->all(),
                ],
                'lesson_types_week' => [
                    'labels' => array_keys($lessonTypeCounts),
                    'values' => array_values($lessonTypeCounts),
                ],
            ],
            'meta' => [
                'week_range' => $weekStart->format('d/m').' – '.$weekEnd->format('d/m/Y'),
                'generated_at' => now()->format('H:i d/m/Y'),
            ],
        ];
    }
}
