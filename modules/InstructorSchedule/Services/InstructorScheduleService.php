<?php

namespace Modules\InstructorSchedule\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class InstructorScheduleService
{
    public const LESSON_TYPES = [
        'theory' => [
            'label' => 'Lý thuyết',
            'short_label' => 'LT',
            'icon' => 'bi-journal-text',
        ],
        'practice' => [
            'label' => 'Thực hành',
            'short_label' => 'TH',
            'icon' => 'bi-tools',
        ],
        'self_study' => [
            'label' => 'Tự học',
            'short_label' => 'THọc',
            'icon' => 'bi-person-workspace',
        ],
        'final_exam' => [
            'label' => 'Thi/Kiểm tra',
            'short_label' => 'Thi/KT',
            'icon' => 'bi-clipboard2-check',
        ],
    ];

    /**
     * Get start and end dates of current week (Monday to Sunday)
     */
    public function getCurrentWeekDates(): array
    {
        return $this->getWeekDates(0);
    }

    /**
     * Get start and end dates of a week with offset
     *
     * @param  int  $weekOffset  0 = current week, +1 = next week, -1 = previous week
     */
    public function getWeekDates(int $weekOffset = 0): array
    {
        $now = Carbon::now();

        // Calculate target week
        $targetDate = $now->copy()->addWeeks($weekOffset);

        // Get Monday and Sunday of that week
        $monday = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $targetDate->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'start' => $monday->toDateString(),
            'end' => $sunday->toDateString(),
            'monday' => $monday,
            'sunday' => $sunday,
            'offset' => $weekOffset,
        ];
    }

    /**
     * Build calendar structure from schedule details (flexible: can be 7 days or custom range)
     *
     * @param  Collection  $schedules  Collection of ScheduleDetail models
     * @param  array  $dateRange  Result from getWeekDates() or getDateRangeFromRequest()
     * @return array Calendar structure: [date => [weekday, is_today, periods => [1-9 => detail]]]
     */
    public function buildWeeklyCalendar(Collection $schedules, array $dateRange): array
    {
        $calendar = [];
        $today = Carbon::today()->toDateString();

        // Calculate number of days between start and end date (inclusive)
        $startDate = $dateRange['monday']->copy();
        $endDate = $dateRange['sunday']->copy();
        $totalDays = (int) $startDate->diffInDays($endDate) + 1;

        $currentDate = $startDate->copy();

        // Generate calendar for all days in range (Mon–Sun = 7 ngày)
        for ($i = 0; $i < $totalDays; $i++) {
            $dateString = $currentDate->toDateString();

            // Filter schedules for this specific day
            $daySchedules = $schedules->where('date', $dateString);

            // Build periods array (1-9)
            $periods = [];
            for ($period = 1; $period <= 9; $period++) {
                $detail = $daySchedules->firstWhere('period', $period);
                $periods[$period] = $detail; // null if no schedule
            }

            $calendar[$dateString] = [
                'date' => $dateString,
                'weekday' => $currentDate->locale('vi')->dayName,
                'weekday_short' => $currentDate->locale('vi')->minDayName,
                'day_number' => $currentDate->day,
                'month_number' => $currentDate->month,
                'is_today' => $dateString === $today,
                'periods' => $periods,
            ];

            $currentDate->addDay();
        }

        return $calendar;
    }

    /**
     * Calculate statistics for the selected date range.
     *
     * Mỗi ScheduleDetail tương ứng một tiết trong lịch huấn luyện.
     */
    public function calculateWeekStatistics(Collection $schedules): array
    {
        return $this->calculateRangeStatistics($schedules);
    }

    public function calculateRangeStatistics(Collection $schedules): array
    {
        $schedules = $schedules->values();
        $typeCounts = $this->typeCounts($schedules);
        $totalHours = $schedules->count();

        $teachingDates = $schedules
            ->pluck('date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values();

        $classBreakdown = $schedules
            ->groupBy(fn (ScheduleDetail $detail) => $this->classLabel($detail))
            ->map(function (Collection $rows, string $className): array {
                return [
                    'class_name' => $className,
                    ...$this->typeCounts($rows),
                    'total_hours' => $rows->count(),
                    'subject_count' => $rows->pluck('subject_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc('total_hours')
            ->values()
            ->all();

        $subjectBreakdown = $schedules
            ->groupBy(fn (ScheduleDetail $detail) => (string) ($detail->subject_id ?: 'unclassified'))
            ->map(function (Collection $rows): array {
                $detail = $rows->first();
                $subject = $detail?->subject;

                return [
                    'subject_id' => $detail?->subject_id,
                    'subject_code' => $subject?->code ?: '—',
                    'subject_name' => $subject?->name ?: 'Chưa xác định môn học',
                    ...$this->typeCounts($rows),
                    'total_hours' => $rows->count(),
                    'class_count' => $rows
                        ->map(fn (ScheduleDetail $row) => $this->classLabel($row))
                        ->unique()
                        ->count(),
                ];
            })
            ->sortByDesc('total_hours')
            ->values()
            ->all();

        $dailyBreakdown = $schedules
            ->groupBy(fn (ScheduleDetail $detail) => Carbon::parse($detail->date)->toDateString())
            ->sortKeys()
            ->map(function (Collection $rows, string $date): array {
                $day = Carbon::parse($date)->locale('vi');

                return [
                    'date' => $date,
                    'date_label' => $day->format('d/m/Y'),
                    'weekday' => ucfirst($day->dayName),
                    ...$this->typeCounts($rows),
                    'total_hours' => $rows->count(),
                    'class_count' => $rows
                        ->map(fn (ScheduleDetail $row) => $this->classLabel($row))
                        ->unique()
                        ->count(),
                ];
            })
            ->values()
            ->all();

        $knownTypeHours = array_sum($typeCounts);
        $peakDay = collect($dailyBreakdown)->sortByDesc('total_hours')->first();

        return [
            'total_classes' => count($classBreakdown),
            'total_subjects' => $schedules->pluck('subject_id')->filter()->unique()->count(),
            'total_rooms' => $schedules->pluck('classroom_id')->filter()->unique()->count(),
            'teaching_days' => $teachingDates->count(),
            'theory_hours' => $typeCounts['theory'],
            'practice_hours' => $typeCounts['practice'],
            'self_study_hours' => $typeCounts['self_study'],
            'exam_hours' => $typeCounts['final_exam'],
            'unclassified_hours' => max(0, $totalHours - $knownTypeHours),
            'morning_hours' => $schedules
                ->filter(fn (ScheduleDetail $detail) => (int) $detail->period >= 1 && (int) $detail->period <= 5)
                ->count(),
            'afternoon_hours' => $schedules
                ->filter(fn (ScheduleDetail $detail) => (int) $detail->period >= 6)
                ->count(),
            'average_hours_per_day' => $teachingDates->isNotEmpty()
                ? round($totalHours / $teachingDates->count(), 1)
                : 0,
            'total_hours' => $totalHours,
            'type_breakdown' => collect(self::LESSON_TYPES)
                ->map(function (array $meta, string $type) use ($typeCounts, $totalHours): array {
                    $hours = $typeCounts[$type];

                    return [
                        'key' => $type,
                        ...$meta,
                        'hours' => $hours,
                        'percentage' => $totalHours > 0 ? round(($hours / $totalHours) * 100, 1) : 0,
                    ];
                })
                ->values()
                ->all(),
            'subject_breakdown' => $subjectBreakdown,
            'class_breakdown' => $classBreakdown,
            'daily_breakdown' => $dailyBreakdown,
            'peak_day' => $peakDay,
        ];
    }

    public function getLessonTypes(): array
    {
        return self::LESSON_TYPES;
    }

    /**
     * Get formatted week range string
     *
     * @param  array  $weekDates  Result from getWeekDates()
     * @return string Format: "18/11 - 24/11/2025"
     */
    public function getWeekRangeLabel(array $weekDates): string
    {
        $monday = $weekDates['monday'];
        $sunday = $weekDates['sunday'];

        // If same month
        if ($monday->month === $sunday->month) {
            return sprintf(
                '%d/%d - %d/%d/%d',
                $monday->day,
                $monday->month,
                $sunday->day,
                $sunday->month,
                $sunday->year
            );
        }

        // Different months
        return sprintf(
            '%d/%d - %d/%d/%d',
            $monday->day,
            $monday->month,
            $sunday->day,
            $sunday->month,
            $sunday->year
        );
    }

    /**
     * Get lesson type label with icon
     */
    public function getLessonTypeLabel(string $lessonType): string
    {
        return self::LESSON_TYPES[$lessonType]['label'] ?? 'Chưa xác định';
    }

    /**
     * Get lesson type badge color classes
     */
    public function getLessonTypeBadgeClass(string $lessonType): string
    {
        $classes = [
            'theory' => 'bg-blue-100 text-blue-700',
            'practice' => 'bg-green-100 text-green-700',
            'self_study' => 'bg-gray-100 text-gray-700',
            'final_exam' => 'bg-red-100 text-red-700',
        ];

        return $classes[$lessonType] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Get date range from request (custom range or week-based)
     *
     * @return array Date range with start, end, monday, sunday, offset
     */
    public function getDateRangeFromRequest(Request $request): array
    {
        // Check if custom date range is provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $startDate = Carbon::parse($request->input('date_from'));
            $endDate = Carbon::parse($request->input('date_to'));

            return [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'monday' => $startDate,
                'sunday' => $endDate,
                'offset' => null,
            ];
        }

        // Otherwise use week-based navigation
        return $this->getWeekDates((int) $request->get('week_offset', 0));
    }

    /**
     * Get formatted date range string
     *
     * @param  array  $dateRange  Result from getDateRangeFromRequest()
     * @return string Format: "18/11 - 24/11/2025"
     */
    public function getDateRangeLabel(array $dateRange): string
    {
        $startDate = Carbon::parse($dateRange['start']);
        $endDate = Carbon::parse($dateRange['end']);

        // If same month
        if ($startDate->month === $endDate->month) {
            return sprintf(
                '%d/%d - %d/%d/%d',
                $startDate->day,
                $startDate->month,
                $endDate->day,
                $endDate->month,
                $endDate->year
            );
        }

        // Different months
        return sprintf(
            '%d/%d - %d/%d/%d',
            $startDate->day,
            $startDate->month,
            $endDate->day,
            $endDate->month,
            $endDate->year
        );
    }

    /**
     * Get schedule details for export
     */
    public function getScheduleDetailsForExport(int $instructorId, string $startDate, string $endDate): Collection
    {
        // Get all schedule details within date range
        $details = ScheduleDetail::with([
            'trainingSchedule.classModel',
            'subject',
            'instructor',
            'classroom.building',
        ])
            ->where('instructor_id', $instructorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        // Group by date and class for horizontal layout
        $grouped = $details->groupBy(function ($detail) {
            $className = $detail->trainingSchedule?->classModel?->name ?? 'N/A';

            return $detail->date.'|'.$className;
        });

        // Generate all dates in range
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $result = [];

        // Loop through all dates in range
        $currentDate = $start->copy();
        while ($currentDate->lte($end)) {
            $dateString = $currentDate->toDateString();
            $dateFormatted = $currentDate->format('d/m/Y').' - '.$currentDate->locale('vi')->isoFormat('dddd');

            // Find schedules for this date
            $dateSchedules = $details->where('date', $dateString);

            if ($dateSchedules->isEmpty()) {
                // No schedules for this date - add empty row
                $result[] = [
                    'date' => $dateFormatted,
                    'class_name' => '',
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
            } else {
                // Group by class for this date
                $classesByDate = $dateSchedules->groupBy(function ($detail) {
                    return $detail->trainingSchedule?->classModel?->name ?? 'N/A';
                });

                // Create a row for each class on this date
                foreach ($classesByDate as $className => $classDetails) {
                    // Initialize row with 9 periods - default "Trống lịch" for all
                    $row = [
                        'date' => $dateFormatted,
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

                    // Fill in periods that have schedule details
                    foreach ($classDetails as $detail) {
                        $periodKey = 'period_'.$detail->period;

                        // Use Eloquent getters to avoid magic property warnings and handle missing relations
                        $subject = $detail->getRelationValue('subject');
                        $classroom = $detail->getRelationValue('classroom');
                        $trainingSchedule = $detail->getRelationValue('trainingSchedule');
                        $lessonTypeKey = $detail->getAttribute('lesson_type');

                        // If schedule detail exists but no subject, leave empty (not "Trống lịch")
                        if (! $subject) {
                            $row[$periodKey] = '';
                        } else {
                            // Format: "Viết tắt/Mã/Tên - Type\nLớp: ClassCode\nPhòng: Room"
                            $lessonType = $this->getLessonTypeAbbreviation($lessonTypeKey);
                            $classCode = $trainingSchedule?->classModel?->code ?? 'N/A';
                            $subjectLabel = trim((string) ($subject->abbreviation ?? ''))
                                ?: trim((string) ($subject->code ?? ''))
                                ?: (string) ($subject->name ?? 'N/A');
                            $line1 = $subjectLabel.' - '.$lessonType;
                            $line2 = 'Lớp: '.$classCode;
                            $line3 = 'Phòng: '.($classroom->name ?? 'N/A');
                            $row[$periodKey] = $line1."\n".$line2."\n".$line3;
                        }
                    }

                    $result[] = $row;
                }
            }

            $currentDate->addDay();
        }

        return collect($result);
    }

    /**
     * Get lesson type abbreviation for export (private helper)
     */
    private function getLessonTypeAbbreviation(string $lessonType): string
    {
        return self::LESSON_TYPES[$lessonType]['short_label'] ?? 'LT';
    }

    /**
     * @return array{theory:int,practice:int,self_study:int,final_exam:int}
     */
    private function typeCounts(Collection $schedules): array
    {
        return [
            'theory' => $schedules->where('lesson_type', 'theory')->count(),
            'practice' => $schedules->where('lesson_type', 'practice')->count(),
            'self_study' => $schedules->where('lesson_type', 'self_study')->count(),
            'final_exam' => $schedules->where('lesson_type', 'final_exam')->count(),
        ];
    }

    private function classLabel(ScheduleDetail $detail): string
    {
        $class = $detail->trainingSchedule?->classModel;

        return trim((string) ($class?->name ?: $class?->code ?: $detail->trainingSchedule?->class_code))
            ?: 'Chưa xác định lớp';
    }
}
