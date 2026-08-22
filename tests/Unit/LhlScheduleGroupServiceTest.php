<?php

namespace Tests\Unit;

use Modules\Classroom\Models\Classroom;
use Modules\ExportTemplates\Data\LhlScheduleGroupService;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Services\LhlPeriodLayoutSelector;
use Modules\TrainingSchedule\Services\TrainingExportService;
use Tests\TestCase;

class LhlScheduleGroupServiceTest extends TestCase
{
    public function test_it_groups_only_consecutive_periods_with_same_rendered_data(): void
    {
        $ttt = new Subject(['name' => 'Thuốc thông thường', 'code' => 'TTT', 'abbreviation' => 'TTT']);
        $ttt->id = 1;
        $gpsl = new Subject(['name' => 'Giải phẫu sinh lý', 'code' => 'GPSL', 'abbreviation' => 'GPSL']);
        $gpsl->id = 2;
        $teacher = new Instructor(['name' => 'Nguyễn Văn Minh', 'code' => 'GV-01']);
        $teacher->id = 1;
        $room = new Classroom(['name' => '101']);
        $room->id = 1;

        $details = collect();
        foreach (range(1, 9) as $period) {
            $subject = in_array($period, [4, 5], true) ? $gpsl : $ttt;
            $details->push($this->detail($period, $subject, $teacher, $room));
        }

        $groups = (new LhlScheduleGroupService)->group($details);

        $this->assertSame(['1-3', '4-5', '6-9'], array_column($groups, 'period_label'));
        $this->assertSame(['TTT', 'GPSL', 'TTT'], array_column($groups, 'subject_short_name'));
    }

    public function test_legacy_export_payload_keeps_repeated_subject_groups_without_color(): void
    {
        $ttt = new Subject(['name' => 'Thuốc thông thường', 'code' => 'TTT', 'abbreviation' => 'TTT']);
        $ttt->id = 1;
        $gpsl = new Subject(['name' => 'Giải phẫu sinh lý', 'code' => 'GPSL', 'abbreviation' => 'GPSL']);
        $gpsl->id = 2;
        $teacher = new Instructor(['name' => 'Nguyễn Văn Minh', 'code' => 'GV-01']);
        $teacher->id = 1;
        $room = new Classroom(['name' => '101']);
        $room->id = 1;
        $details = collect();

        foreach (range(1, 9) as $period) {
            $details->push($this->detail(
                $period,
                in_array($period, [4, 5], true) ? $gpsl : $ttt,
                $teacher,
                $room
            ));
        }

        $method = new \ReflectionMethod(TrainingExportService::class, 'cellsFromDetails');
        $cells = $method->invoke(app(TrainingExportService::class), $details);

        $this->assertSame(
            ["1-3\nTTT", "4-5\nGPSL", "6-9\nTTT"],
            $cells->pluck('label')->all()
        );
        $this->assertTrue($cells->every(
            fn (object $cell): bool => ! property_exists($cell, 'color')
        ));
    }

    public function test_it_keeps_two_three_and_three_four_ranges_on_different_days(): void
    {
        $ttt = new Subject(['name' => 'Thuốc thông thường', 'code' => 'TTT', 'abbreviation' => 'TTT']);
        $ttt->id = 1;
        $gpsl = new Subject(['name' => 'Giải phẫu sinh lý', 'code' => 'GPSL', 'abbreviation' => 'GPSL']);
        $gpsl->id = 2;
        $teacher = new Instructor(['name' => 'Nguyễn Văn Minh', 'code' => 'GV-01']);
        $teacher->id = 1;
        $room = new Classroom(['name' => '101']);
        $room->id = 1;

        $details = collect([
            $this->detail(2, $ttt, $teacher, $room),
            $this->detail(3, $ttt, $teacher, $room),
            tap($this->detail(3, $gpsl, $teacher, $room), fn (ScheduleDetail $detail) => $detail->date = '2026-03-03'),
            tap($this->detail(4, $gpsl, $teacher, $room), fn (ScheduleDetail $detail) => $detail->date = '2026-03-03'),
        ]);

        $groups = (new LhlScheduleGroupService)->group($details);

        $this->assertSame(['2-3', '3-4'], array_column($groups, 'period_label'));
        $this->assertSame(['TTT', 'GPSL'], array_column($groups, 'subject_short_name'));
    }

    public function test_same_subject_stays_one_full_session_group_and_preserves_changed_teacher_values(): void
    {
        $subject = new Subject(['name' => 'Thuốc thông thường', 'code' => 'TTT', 'abbreviation' => 'TTT']);
        $subject->id = 1;
        $firstTeacher = new Instructor(['name' => 'Giảng viên A', 'code' => 'GV-A']);
        $firstTeacher->id = 1;
        $secondTeacher = new Instructor(['name' => 'Giảng viên B', 'code' => 'GV-B']);
        $secondTeacher->id = 2;
        $room = new Classroom(['name' => '101']);
        $room->id = 1;
        $details = collect();

        foreach (range(1, 5) as $period) {
            $details->push($this->detail(
                $period,
                $subject,
                $period <= 2 ? $firstTeacher : $secondTeacher,
                $room
            ));
        }

        $groups = (new LhlScheduleGroupService)->group($details);

        $this->assertCount(1, $groups);
        $this->assertSame('1-5', $groups[0]['period_label']);
        $this->assertSame("Giảng viên A\nGiảng viên B", $groups[0]['teacher_name']);
    }

    public function test_layout_selector_always_uses_the_fixed_three_slot_lhl_grid(): void
    {
        $ttt = new Subject(['name' => 'Thuốc thông thường', 'code' => 'TTT', 'abbreviation' => 'TTT']);
        $ttt->id = 1;
        $teacher = new Instructor(['name' => 'Nguyễn Văn Minh', 'code' => 'GV-01']);
        $teacher->id = 1;
        $room = new Classroom(['name' => '101']);
        $room->id = 1;

        $classic = collect();
        foreach (range(1, 9) as $period) {
            $classic->push($this->detail($period, $ttt, $teacher, $room));
        }

        $selector = app(LhlPeriodLayoutSelector::class);
        $this->assertSame(LhlPeriodLayoutSelector::GROUPED_PERIODS, $selector->selectFromDetails($classic));

        $split = collect();
        foreach ([2, 3] as $period) {
            $split->push($this->detail($period, $ttt, $teacher, $room));
        }
        $this->assertSame(
            LhlPeriodLayoutSelector::GROUPED_PERIODS,
            $selector->selectFromDetails($split)
        );
    }

    private function detail(
        int $period,
        Subject $subject,
        Instructor $teacher,
        Classroom $room
    ): ScheduleDetail {
        $detail = new ScheduleDetail([
            'date' => '2026-03-02',
            'period' => $period,
            'subject_id' => $subject->id,
            'instructor_id' => $teacher->id,
            'classroom_id' => $room->id,
            'lesson_type' => 'theory',
        ]);
        $detail->setRelation('subject', $subject);
        $detail->setRelation('instructor', $teacher);
        $detail->setRelation('classroom', $room);

        return $detail;
    }
}
