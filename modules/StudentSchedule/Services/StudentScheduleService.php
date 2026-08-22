<?php

namespace Modules\StudentSchedule\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class StudentScheduleService
{
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

        // Calculate number of days between start and end date
        $startDate = $dateRange['monday']->copy();
        $endDate = $dateRange['sunday']->copy();
        $totalDays = $startDate->diffInDays($endDate) + 1; // +1 to include end date

        $currentDate = $startDate->copy();

        // Generate calendar for all days in range
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
     * Calculate statistics for the displayed week (student-specific)
     *
     * @param  Collection  $schedules  Collection of ScheduleDetail models
     * @return array Statistics [total_subjects, theory_hours, practice_hours, total_hours]
     */
    public function calculateWeekStatistics(Collection $schedules): array
    {
        // Count unique subjects
        $uniqueSubjects = $schedules->pluck('subject_id')
            ->filter()
            ->unique()
            ->count();

        // Count hours by lesson type
        $theoryHours = $schedules->where('lesson_type', 'theory')->count();
        $practiceHours = $schedules->where('lesson_type', 'practice')->count();
        $selfStudyHours = $schedules->where('lesson_type', 'self_study')->count();
        $examHours = $schedules->where('lesson_type', 'final_exam')->count();

        return [
            'total_subjects' => $uniqueSubjects,
            'theory_hours' => $theoryHours,
            'practice_hours' => $practiceHours,
            'self_study_hours' => $selfStudyHours,
            'exam_hours' => $examHours,
            'total_hours' => $schedules->count(),
        ];
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
    public function getScheduleDetailsForExport(int $trainingScheduleId, string $startDate, string $endDate): Collection
    {
        // Get all schedule details within date range
        $details = ScheduleDetail::with([
            'subject',
            'instructor',
            'classroom.building',
            'trainingSchedule.classModel',
        ])
            ->where('training_schedule_id', $trainingScheduleId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        // Generate all dates in range
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $result = [];

        // Get class name from first detail (all have same training schedule)
        $className = $details->first()?->trainingSchedule?->classModel?->name ?? 'N/A';

        // Loop through all dates in range
        $currentDate = $start->copy();
        while ($currentDate->lte($end)) {
            $dateString = $currentDate->toDateString();
            $dateFormatted = $currentDate->format('d/m/Y').' - '.$currentDate->locale('vi')->isoFormat('dddd');

            // Find schedules for this date
            $dayDetails = $details->where('date', $dateString);

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
            foreach ($dayDetails as $detail) {
                $periodKey = 'period_'.$detail->period;

                // Use Eloquent getters to avoid magic property warnings and handle missing relations
                $subject = $detail->getRelationValue('subject');
                $instructor = $detail->getRelationValue('instructor');
                $classroom = $detail->getRelationValue('classroom');
                $lessonTypeKey = $detail->getAttribute('lesson_type');

                // If schedule detail exists but no subject, leave empty (not "Trống lịch")
                if (! $subject) {
                    $row[$periodKey] = '';
                } else {
                    // Format: "Viết tắt/Mã/Tên - Type\nInstructor - Room"
                    $lessonType = $this->getLessonTypeAbbreviation($lessonTypeKey);
                    $subjectLabel = trim((string) ($subject->abbreviation ?? ''))
                        ?: trim((string) ($subject->code ?? ''))
                        ?: (string) ($subject->name ?? 'N/A');
                    $line1 = $subjectLabel.' - '.$lessonType;
                    $line2 = ($instructor->name ?? 'N/A').' - '.($classroom->name ?? 'N/A');
                    $row[$periodKey] = $line1."\n".$line2;
                }
            }

            $result[] = $row;
            $currentDate->addDay();
        }

        return collect($result);
    }

    /**
     * Get lesson type abbreviation for export (private helper)
     */
    private function getLessonTypeAbbreviation(string $lessonType): string
    {
        $abbreviations = [
            'theory' => 'LT',      // Lý thuyết
            'practice' => 'TH',    // Thực hành
            'self_study' => 'Ôn',  // Tự học
            'final_exam' => 'Thi/KT', // Thi
        ];

        return $abbreviations[$lessonType] ?? 'LT';
    }
}
