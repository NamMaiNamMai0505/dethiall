<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;

/**
 * Lịch chuyên kiểm thử gom tiết và xuất LHL trên /training-schedules/calendar.
 *
 * Chạy:
 *   php artisan db:seed --class=TrainingScheduleExportDemoSeeder
 */
class TrainingScheduleExportDemoSeeder extends Seeder
{
    public const CODE = 'DEMO-LHL-EXPORT-RANGES';

    public function run(): void
    {
        $adminId = (int) (User::query()->where('email', 'admin@example.com')->value('id')
            ?? User::query()->value('id')
            ?? 1);
        $class = ClassModel::query()->orderBy('id')->first();

        if (! $class) {
            $this->command?->error('Cần ít nhất 1 lớp trước khi tạo demo xuất LHL.');

            return;
        }

        DB::transaction(function () use ($adminId, $class): void {
            $subjects = collect([
                $this->demoSubject(
                    'DEMO-TTT',
                    'Thuốc thông thường',
                    'TTT',
                    (int) $class->specialization_id,
                    $adminId
                ),
                $this->demoSubject(
                    'DEMO-GPSL',
                    'Giải phẫu sinh lý',
                    'GPSL',
                    (int) $class->specialization_id,
                    $adminId
                ),
                $this->demoSubject(
                    'DEMO-DDCB',
                    'Điều dưỡng cơ bản',
                    'ĐDCB',
                    (int) $class->specialization_id,
                    $adminId
                ),
                Subject::query()->where('code', 'NPL')->first()
                    ?? $this->demoSubject(
                        'DEMO-NPL',
                        'Ngày pháp luật',
                        'NPL',
                        (int) $class->specialization_id,
                        $adminId
                    ),
            ]);

            $oldSchedules = TrainingSchedule::withTrashed()->where('code', self::CODE)->get();
            if ($oldSchedules->isNotEmpty()) {
                ScheduleDetail::query()->whereIn('training_schedule_id', $oldSchedules->pluck('id'))->delete();
                TrainingSchedule::withTrashed()->whereIn('id', $oldSchedules->pluck('id'))->forceDelete();
            }

            $latestEndDate = TrainingSchedule::query()
                ->where('code', '!=', self::CODE)
                ->max('end_date');
            $candidate = now()->addDay()->startOfDay();
            if ($latestEndDate && Carbon::parse($latestEndDate)->gte($candidate)) {
                $candidate = Carbon::parse($latestEndDate)->addDay()->startOfDay();
            }
            $weekStart = $candidate->copy()->startOfWeek(Carbon::MONDAY);
            if ($weekStart->lt($candidate)) {
                $weekStart->addWeek();
            }
            $scenarios = $this->buildScenarios($weekStart, 17);

            $resolved = [];
            foreach ($scenarios as $scenario) {
                $periods = collect($scenario['groups'])
                    ->flatMap(fn (array $group): array => $group['periods'])
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $slot = $this->findFreeWeekdaySlot($scenario['preferred_date'], $periods);
                $resolved[] = $scenario + $slot;
            }

            $start = collect($resolved)->min(fn (array $scenario): string => $scenario['date']->toDateString());
            $end = collect($resolved)->max(fn (array $scenario): string => $scenario['date']->toDateString());
            $schedule = TrainingSchedule::query()->create([
                'name' => 'DEMO XUẤT LHL 4 THÁNG · ĐẦY ĐỦ NHÓM TIẾT',
                'code' => self::CODE,
                'abbreviation' => 'LHL 2-3/3-4',
                'specialization_id' => $class->specialization_id,
                'class_id' => $class->id,
                'class_code' => $class->code,
                'classroom_id' => $resolved[0]['classroom_id'],
                'academic_year' => $this->academicYearFor(Carbon::parse($start)),
                'semester' => 'semester_2',
                'start_date' => $start,
                'end_date' => $end,
                'weekly_schedule' => null,
                'is_active' => true,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            foreach ($resolved as $scenario) {
                foreach ($scenario['groups'] as $group) {
                    $subject = $subjects->get($group['subject']) ?? $subjects->first();
                    foreach ($group['periods'] as $period) {
                        ScheduleDetail::query()->create([
                            'training_schedule_id' => $schedule->id,
                            'date' => $scenario['date']->toDateString(),
                            'period' => $period,
                            'subject_id' => $subject->id,
                            'subject_lesson_id' => null,
                            'instructor_id' => $scenario['instructor_id'],
                            'classroom_id' => $scenario['classroom_id'],
                            'lesson_type' => $group['lesson_type'],
                        ]);
                    }
                }
            }

            $this->command?->info("✓ Đã tạo lịch #{$schedule->id}: {$schedule->name}");
            $this->command?->line("  Mã chọn trên form xuất: {$schedule->code}");
            $this->command?->line('  Tổng số ngày có lịch: '.count($resolved));
            $this->command?->line('  Tổng số chi tiết tiết học: '.ScheduleDetail::query()
                ->where('training_schedule_id', $schedule->id)
                ->count());
            foreach (array_slice($resolved, 0, 5) as $scenario) {
                $labels = collect($scenario['groups'])
                    ->map(fn (array $group): string => min($group['periods']).'–'.max($group['periods']))
                    ->implode(' / ');
                $this->command?->line(
                    "  {$scenario['date']->format('d/m/Y')} · {$scenario['label']} · {$labels}"
                );
            }
            $this->command?->line('  ... dữ liệu tiếp tục trên 17 tuần, từ Thứ 2 đến Thứ 6.');
            $this->command?->line("  Khoảng xuất: {$start} → {$end}");
        });
    }

    /**
     * @return list<array{label:string,preferred_date:Carbon,groups:list<array{periods:list<int>,subject:int,lesson_type:string}>}>
     */
    private function buildScenarios(Carbon $weekStart, int $weekCount): array
    {
        $patterns = [
            [
                ['periods' => [2, 3], 'subject' => 0, 'lesson_type' => 'theory'],
                ['periods' => [4, 5], 'subject' => 1, 'lesson_type' => 'theory'],
                ['periods' => [6, 7, 8, 9], 'subject' => 2, 'lesson_type' => 'theory'],
            ],
            [
                ['periods' => [1, 2, 3], 'subject' => 2, 'lesson_type' => 'theory'],
                ['periods' => [4, 5], 'subject' => 0, 'lesson_type' => 'theory'],
                ['periods' => [6, 7, 8, 9], 'subject' => 1, 'lesson_type' => 'practice'],
            ],
            [
                ['periods' => [1, 2], 'subject' => 0, 'lesson_type' => 'theory'],
                ['periods' => [3, 4], 'subject' => 1, 'lesson_type' => 'theory'],
                ['periods' => [5], 'subject' => 2, 'lesson_type' => 'theory'],
                ['periods' => [6, 7, 8, 9], 'subject' => 0, 'lesson_type' => 'theory'],
            ],
            [
                ['periods' => [1, 2, 3], 'subject' => 0, 'lesson_type' => 'theory'],
                ['periods' => [4, 5], 'subject' => 1, 'lesson_type' => 'practice'],
                ['periods' => [6, 7, 8, 9], 'subject' => 0, 'lesson_type' => 'theory'],
            ],
            [
                ['periods' => [1, 2, 3], 'subject' => 3, 'lesson_type' => 'theory'],
                ['periods' => [4, 5], 'subject' => 2, 'lesson_type' => 'theory'],
                ['periods' => [6, 7, 8, 9], 'subject' => 3, 'lesson_type' => 'theory'],
            ],
            [
                ['periods' => [1, 2, 3], 'subject' => 1, 'lesson_type' => 'theory'],
                ['periods' => [4, 5], 'subject' => 2, 'lesson_type' => 'practice'],
                ['periods' => [6, 7, 8], 'subject' => 0, 'lesson_type' => 'theory'],
            ],
        ];

        $scenarios = [];
        for ($week = 0; $week < $weekCount; $week++) {
            for ($day = 0; $day < 5; $day++) {
                $patternIndex = (($week * 2) + $day) % count($patterns);
                $scenarios[] = [
                    'label' => 'Tuần '.($week + 1).' · mẫu nhóm '.($patternIndex + 1),
                    'preferred_date' => $weekStart->copy()->addWeeks($week)->addDays($day),
                    'groups' => $patterns[$patternIndex],
                ];
            }
        }

        return $scenarios;
    }

    /**
     * @param  list<int>  $periods
     * @return array{date:Carbon,instructor_id:int,classroom_id:int}
     */
    private function findFreeWeekdaySlot(Carbon $preferredDate, array $periods): array
    {
        $date = $preferredDate->copy()->startOfDay();

        for ($week = 0; $week < 12; $week++) {
            $dateString = $date->toDateString();
            $instructorId = Instructor::query()
                ->whereNotIn('id', ScheduleDetail::query()
                    ->whereDate('date', $dateString)
                    ->whereIn('period', $periods)
                    ->pluck('instructor_id'))
                ->orderBy('id')
                ->value('id');
            $classroomId = Classroom::query()
                ->where('status', true)
                ->whereNotIn('id', ScheduleDetail::query()
                    ->whereDate('date', $dateString)
                    ->whereIn('period', $periods)
                    ->pluck('classroom_id'))
                ->orderByDesc('id')
                ->value('id');

            if ($instructorId && $classroomId) {
                return [
                    'date' => $date,
                    'instructor_id' => (int) $instructorId,
                    'classroom_id' => (int) $classroomId,
                ];
            }

            $date->addWeek();
        }

        throw new \RuntimeException('Không tìm được ngày trong tuần trống để tạo lịch demo xuất LHL.');
    }

    private function academicYearFor(Carbon $date): string
    {
        $year = (int) $date->year;

        return $date->month >= 8
            ? $year.'-'.($year + 1)
            : ($year - 1).'-'.$year;
    }

    private function demoSubject(
        string $code,
        string $name,
        string $abbreviation,
        int $specializationId,
        int $adminId
    ): Subject {
        $subject = Subject::withTrashed()->firstOrNew(['code' => $code]);
        $subject->fill([
            'name' => $name,
            'abbreviation' => $abbreviation,
            'description' => 'Dữ liệu demo kiểm thử mẫu xuất LHL chia nhóm tiết.',
            'specialization_id' => $specializationId,
            'credits' => 1,
            'theory_hours' => 45,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'exam_hours' => 0,
            'review_hours' => 0,
            'level' => 'basic',
            'prerequisites' => null,
            'assessment_method' => 'combined',
            'is_required' => true,
            'is_special_activity' => false,
            'is_active' => true,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
        $subject->deleted_at = null;
        $subject->save();

        return $subject;
    }
}
