<?php

namespace Modules\Dashboard\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Dashboard\Support\DashboardScope;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class AccountStatisticsService
{
    /**
     * Build the automatic dashboard snapshot for the current account scope.
     */
    public function build(array $scope): array
    {
        $today = Carbon::today();
        $periodStart = $today->copy()->startOfMonth();
        $periodEnd = $today->copy()->endOfMonth();

        $periodQuery = ScheduleDetail::query()
            ->with([
                'subject:id,name,code',
                'instructor:id,name,code,unit_id',
                'classroom:id,name',
                'trainingSchedule.classModel:id,name,code',
            ])
            ->whereHas('trainingSchedule', fn ($query) => $query->where('is_active', true))
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
        DashboardScope::applyToScheduleQuery($periodQuery, $scope);
        $periodRows = $periodQuery->orderBy('date')->orderBy('period')->get();

        $todayRows = $periodRows
            ->filter(fn (ScheduleDetail $detail) => Carbon::parse($detail->date)->isSameDay($today))
            ->values();

        $upcomingQuery = ScheduleDetail::query()
            ->with([
                'subject:id,name,code',
                'instructor:id,name,code,unit_id',
                'classroom:id,name',
                'trainingSchedule.classModel:id,name,code',
            ])
            ->whereHas('trainingSchedule', fn ($query) => $query->where('is_active', true))
            ->whereBetween('date', [
                $today->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ]);
        DashboardScope::applyToScheduleQuery($upcomingQuery, $scope);
        $upcoming = $upcomingQuery
            ->orderBy('date')
            ->orderBy('period')
            ->limit(10)
            ->get()
            ->map(fn (ScheduleDetail $detail) => $this->scheduleRow($detail))
            ->values()
            ->all();

        $typeCounts = $this->typeCounts($periodRows);
        $classCount = $periodRows
            ->map(fn (ScheduleDetail $detail) => $this->classKey($detail))
            ->filter()
            ->unique()
            ->count();

        $daily = $periodRows
            ->groupBy(fn (ScheduleDetail $detail) => Carbon::parse($detail->date)->toDateString())
            ->sortKeys()
            ->map(fn (Collection $rows, string $date) => [
                'date' => Carbon::parse($date)->format('d/m'),
                'lessons' => $rows->count(),
            ])
            ->values()
            ->all();

        return [
            'scope_type' => $scope['type'],
            'scope_label' => $scope['label'],
            'period_label' => 'Tháng '.$periodStart->format('m/Y'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'today_lessons' => $todayRows->count(),
            'total_lessons' => $periodRows->count(),
            'theory_lessons' => $typeCounts['theory'],
            'practice_lessons' => $typeCounts['practice'],
            'self_study_lessons' => $typeCounts['self_study'],
            'exam_lessons' => $typeCounts['final_exam'],
            'teaching_days' => $periodRows
                ->pluck('date')
                ->filter()
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->count(),
            'classes_count' => $classCount,
            'subjects_count' => $periodRows->pluck('subject_id')->filter()->unique()->count(),
            'instructors_count' => $periodRows->pluck('instructor_id')->filter()->unique()->count(),
            'rooms_count' => $periodRows->pluck('classroom_id')->filter()->unique()->count(),
            'type_chart' => [
                'labels' => ['Lý thuyết', 'Thực hành', 'Tự học', 'Thi/KT'],
                'data' => array_values($typeCounts),
            ],
            'daily_chart' => [
                'labels' => array_column($daily, 'date'),
                'data' => array_column($daily, 'lessons'),
            ],
            'upcoming' => $upcoming,
        ];
    }

    /**
     * @return array{theory:int,practice:int,self_study:int,final_exam:int}
     */
    private function typeCounts(Collection $rows): array
    {
        return [
            'theory' => $rows->where('lesson_type', 'theory')->count(),
            'practice' => $rows->where('lesson_type', 'practice')->count(),
            'self_study' => $rows->where('lesson_type', 'self_study')->count(),
            'final_exam' => $rows->where('lesson_type', 'final_exam')->count(),
        ];
    }

    private function classKey(ScheduleDetail $detail): ?string
    {
        $class = $detail->trainingSchedule?->classModel;
        $value = $class?->id
            ?: $class?->code
            ?: $detail->trainingSchedule?->class_code;

        return filled($value) ? (string) $value : null;
    }

    private function scheduleRow(ScheduleDetail $detail): array
    {
        $date = Carbon::parse($detail->date)->locale('vi');

        return [
            'date' => $date->toDateString(),
            'date_label' => $date->format('d/m/Y'),
            'weekday' => ucfirst($date->dayName),
            'period' => (int) $detail->period,
            'subject' => $detail->subject?->name ?? 'Chưa xác định môn học',
            'class' => $detail->trainingSchedule?->classModel?->code
                ?? $detail->trainingSchedule?->class_code
                ?? 'Chưa xác định lớp',
            'instructor' => $detail->instructor?->name ?? 'Chưa phân công',
            'room' => $detail->classroom?->name ?? 'Chưa có phòng',
            'lesson_type' => $detail->lesson_type,
        ];
    }
}
