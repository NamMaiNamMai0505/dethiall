<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Instructor\Models\Instructor;
use Modules\ScheduleDetail\Models\ScheduleDetail;

/**
 * @extends Factory<ScheduleDetail>
 */
class ScheduleDetailFactory extends Factory
{
    protected $model = ScheduleDetail::class;

    public function definition(): array
    {
        $ids = $this->seedRequiredIds();

        return [
            'training_schedule_id' => $ids['training_schedule_id'],
            'date' => now()->toDateString(),
            'period' => fake()->numberBetween(1, 9),
            'subject_id' => $ids['subject_id'],
            'subject_lesson_id' => null,
            'instructor_id' => $ids['instructor_id'],
            'classroom_id' => $ids['classroom_id'],
            'lesson_type' => fake()->randomElement(['theory', 'practice', 'self_study', 'final_exam']),
        ];
    }

    /**
     * @return array{training_schedule_id:int,subject_id:int,instructor_id:int,classroom_id:int}
     */
    protected function seedRequiredIds(): array
    {
        $tag = substr(uniqid(), -8);

        $instructor = Instructor::factory()->create();

        $specId = DB::table('specializations')->insertGetId([
            'name' => 'Spec '.$tag,
            'code' => 'SP-'.$tag,
            'description' => null,
            'level' => 'beginner',
            'duration_months' => 12,
            'certification_type' => 'certificate',
            'is_active' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectCols = [
            'name' => 'Mon '.$tag,
            'code' => 'SUB-'.$tag,
            'description' => null,
            'specialization_id' => $specId,
            'credits' => 1,
            'theory_hours' => 1,
            'practice_hours' => 0,
            'self_study_hours' => 0,
            'level' => 'basic',
            'assessment_method' => 'exam',
            'is_required' => 1,
            'is_active' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('subjects', 'semester')) {
            $subjectCols['semester'] = 1;
        }
        $subjectId = DB::table('subjects')->insertGetId($subjectCols);

        $classroomId = DB::table('classrooms')->insertGetId([
            'name' => 'Room '.$tag,
            'status' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tsId = DB::table('training_schedules')->insertGetId([
            'name' => 'TS '.$tag,
            'code' => 'TS-'.$tag,
            'specialization_id' => $specId,
            'class_id' => null,
            'class_code' => null,
            'academic_year' => now()->year.'-'.(now()->year + 1),
            'semester' => 'semester_1',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'weekly_schedule' => null,
            'is_active' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'training_schedule_id' => (int) $tsId,
            'subject_id' => (int) $subjectId,
            'instructor_id' => (int) $instructor->id,
            'classroom_id' => (int) $classroomId,
        ];
    }
}
