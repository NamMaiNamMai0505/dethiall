<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\ScheduleDetail\Models\ScheduleDetail;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Exports\FacultyTrainingPlanExport;
use Tests\TestCase;

class FacultyTrainingPlanGroupingTest extends TestCase
{
    public function test_faculty_plan_keeps_the_middle_four_to_five_period_group(): void
    {
        $ttt = new Subject(['name' => 'Thể thao', 'abbreviation' => 'TTT']);
        $ttt->id = 1;
        $gpsl = new Subject(['name' => 'Giáo dục pháp luật', 'abbreviation' => 'GPSL']);
        $gpsl->id = 2;
        $details = new Collection;

        foreach (range(1, 9) as $period) {
            $subject = in_array($period, [4, 5], true) ? $gpsl : $ttt;
            $detail = new ScheduleDetail([
                'date' => '2026-08-17',
                'period' => $period,
                'subject_id' => $subject->id,
                'lesson_type' => 'theory',
            ]);
            $detail->setRelation('subject', $subject);
            $detail->setRelation('subjectLesson', null);
            $detail->setRelation('classroom', null);
            $detail->setRelation('instructor', null);
            $details->push($detail);
        }

        $rows = FacultyTrainingPlanExport::rowsFromDetails($details);

        $this->assertSame(['1-3', '4-5', '6-9'], $rows->pluck('period_label')->all());
        $this->assertSame(['TTT', 'GPSL', 'TTT'], $rows->pluck('subject_short')->all());
    }
}
