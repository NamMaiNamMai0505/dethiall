<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Class\Models\ClassModel;
use Modules\Grades\Models\GradeBook;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Models\LmsGradebookRow;
use Modules\Lms\Models\LmsLesson;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\Lms\Services\LmsGradeTransferService;
use Modules\Lms\Support\LmsSettings;
use Modules\Subject\Models\SubjectLesson;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Tests\TestCase;

class LmsRoadmapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_curriculum_automatically_enrols_specialization_subjects_and_assigned_instructors(): void
    {
        $owner = User::factory()->create(['status' => 1]);
        $instructor = Instructor::query()->create([
            'name' => 'GV ngành tự động',
            'code' => 'GV-AUTO-CURRICULUM',
            'email' => 'gv-auto-curriculum@test.local',
            'status' => 'active',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $lecturerUser = User::factory()->create([
            'instructor_id' => $instructor->id,
            'user_type' => 'instructor',
            'status' => 1,
        ]);
        $specializationId = DB::table('specializations')->insertGetId([
            'name' => 'Ngành tự động LMS', 'code' => 'SPEC-AUTO-LMS', 'level' => 'beginner',
            'duration_months' => 12, 'certification_type' => 'certificate', 'is_active' => 1,
            'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectIds = collect(['Môn A', 'Môn B'])->map(fn (string $name, int $index) => DB::table('subjects')->insertGetId([
            'name' => $name, 'code' => 'AUTO-LMS-'.($index + 1), 'specialization_id' => $specializationId,
            'credits' => 1, 'theory_hours' => 2, 'practice_hours' => 0, 'self_study_hours' => 0,
            'level' => 'basic', 'semester' => 'semester_1', 'assessment_method' => 'exam',
            'is_required' => 1, 'is_active' => 1,
            'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]));
        DB::table('teaching_assignment')->insert([
            'instructor_id' => $instructor->id,
            'subject_id' => $subjectIds->first(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $class = ClassModel::query()->create([
            'name' => 'Lớp ngành tự động', 'code' => 'CLS-AUTO-LMS',
            'specialization_id' => $specializationId, 'instructor_id' => $instructor->id,
            'start_date' => now(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Test', 'max_students' => 30, 'current_students' => 1,
            'is_active' => true, 'created_by' => $owner->id, 'updated_by' => $owner->id,
        ]);
        $student = User::factory()->create([
            'class_id' => $class->id,
            'user_type' => 'student',
            'status' => 1,
        ]);

        $service = app(LmsCourseProvisioningService::class);
        $first = $service->provisionFromClassCurriculum($class, $owner);
        $second = $service->provisionFromClassCurriculum($class, $owner);

        $this->assertSame(2, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(2, LmsCourse::query()->where('class_id', $class->id)->count());
        $assignedCourse = LmsCourse::query()->where('subject_id', $subjectIds->first())->firstOrFail();
        $this->assertDatabaseHas('lms_course_instructors', [
            'lms_course_id' => $assignedCourse->id,
            'instructor_id' => $instructor->id,
            'source' => 'teaching_assignment',
        ]);
        $this->assertDatabaseHas('lms_course_members', [
            'lms_course_id' => $assignedCourse->id,
            'user_id' => $student->id,
            'role' => 'student',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('lms_course_members', [
            'lms_course_id' => $assignedCourse->id,
            'user_id' => $lecturerUser->id,
            'role' => 'lecturer',
            'status' => 'active',
        ]);
    }

    public function test_lms_grade_weights_are_loaded_from_shared_settings_and_normalized(): void
    {
        foreach ([
            'grade_weight_assignments' => 20,
            'grade_weight_exams' => 50,
            'grade_weight_attendance' => 20,
            'grade_weight_progress' => 10,
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['portal' => 'lms', 'key' => $key],
                ['value' => $value, 'type' => 'number']
            );
        }

        $this->assertSame([
            'assignments' => 0.2,
            'exams' => 0.5,
            'attendance' => 0.2,
            'progress' => 0.1,
        ], LmsSettings::gradeWeights());
    }

    public function test_training_schedule_provision_is_idempotent_and_syncs_exact_roster_content_and_attendance(): void
    {
        $owner = User::factory()->create(['status' => 1]);
        $instructor = Instructor::query()->create([
            'name' => 'Giảng viên tích hợp',
            'code' => 'GV-R2',
            'email' => 'gv-r2@test.local',
            'status' => 'active',
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $lecturerUser = User::factory()->create([
            'instructor_id' => $instructor->id,
            'user_type' => 'instructor',
            'status' => 1,
        ]);

        $specializationId = DB::table('specializations')->insertGetId([
            'name' => 'Ngành LMS R2', 'code' => 'SPEC-LMS-R2', 'level' => 'beginner',
            'duration_months' => 12, 'certification_type' => 'certificate', 'is_active' => 1,
            'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'name' => 'Môn LMS tích hợp', 'code' => 'LMS-R2-K1', 'specialization_id' => $specializationId,
            'credits' => 2, 'theory_hours' => 10, 'practice_hours' => 5, 'self_study_hours' => 5,
            'level' => 'basic', 'assessment_method' => 'combined', 'is_required' => 1, 'is_active' => 1,
            'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Lớp LMS R2', 'code' => 'CLS-LMS-R2', 'specialization_id' => $specializationId,
            'instructor_id' => $instructor->id, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(4),
            'duration_months' => 5, 'management_unit' => 'Test', 'max_students' => 40,
            'current_students' => 1, 'is_active' => 1,
            'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = User::factory()->create([
            'class_id' => $classId,
            'user_type' => 'student',
            'status' => 1,
        ]);
        $staleStudent = User::factory()->create(['user_type' => 'student', 'status' => 1]);
        $classroomId = DB::table('classrooms')->insertGetId([
            'name' => 'P.R2', 'status' => 1, 'created_by' => $owner->id, 'updated_by' => $owner->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $lesson = SubjectLesson::query()->create([
            'subject_id' => $subjectId,
            'code' => 'B1',
            'name' => 'Bài học từ chương trình',
            'lesson_kind' => SubjectLesson::KIND_LESSON,
            'sort_order' => 1,
            'semester' => 1,
        ]);
        $schedule = TrainingSchedule::query()->create([
            'name' => 'Lịch LMS R2', 'code' => 'TS-LMS-R2', 'specialization_id' => $specializationId,
            'class_id' => $classId, 'class_code' => 'CLS-LMS-R2', 'academic_year' => '2026-2027',
            'semester' => 'semester_1', 'start_date' => '2026-08-03', 'end_date' => '2026-12-20',
            'is_active' => true, 'created_by' => $owner->id, 'updated_by' => $owner->id,
        ]);
        $detailId = DB::table('schedule_details')->insertGetId([
            'training_schedule_id' => $schedule->id, 'date' => '2026-08-03', 'period' => 2,
            'subject_id' => $subjectId, 'subject_lesson_id' => $lesson->id,
            'instructor_id' => $instructor->id, 'classroom_id' => $classroomId,
            'lesson_type' => 'theory', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(LmsCourseProvisioningService::class);
        $first = $service->provisionFromTrainingSchedule($schedule, $owner);
        $course = $first['courses']->first();
        LmsCourseMember::query()->create([
            'lms_course_id' => $course->id, 'user_id' => $staleStudent->id,
            'role' => LmsCourseMember::ROLE_STUDENT, 'source' => 'class', 'status' => 'active',
        ]);
        $second = $service->provisionFromTrainingSchedule($schedule, $owner);

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, LmsCourse::query()->where('provision_key', "training-schedule:{$schedule->id}:subject:{$subjectId}")->count());
        $this->assertDatabaseHas('lms_course_members', ['lms_course_id' => $course->id, 'user_id' => $student->id, 'status' => 'active']);
        $this->assertDatabaseHas('lms_course_members', ['lms_course_id' => $course->id, 'user_id' => $lecturerUser->id, 'status' => 'active']);
        $this->assertDatabaseHas('lms_course_members', ['lms_course_id' => $course->id, 'user_id' => $staleStudent->id, 'status' => 'inactive']);
        $this->assertSame(1, LmsLesson::query()->where('lms_course_id', $course->id)->where('subject_lesson_id', $lesson->id)->count());
        $this->assertSame(1, LmsAttendanceSession::query()->where('schedule_detail_id', $detailId)->where('lms_course_id', $course->id)->count());
    }

    public function test_lms_score_transfer_creates_draft_and_never_overwrites_approved_grade_book(): void
    {
        $actor = User::factory()->create(['status' => 1]);
        $instructor = Instructor::query()->create([
            'name' => 'GV chuyển điểm', 'code' => 'GV-GRADE-BRIDGE', 'email' => 'bridge@test.local',
            'status' => 'active', 'created_by' => $actor->id, 'updated_by' => $actor->id,
        ]);
        $specializationId = DB::table('specializations')->insertGetId([
            'name' => 'Ngành cầu điểm', 'code' => 'SPEC-GRADE-BRIDGE', 'level' => 'beginner',
            'duration_months' => 12, 'certification_type' => 'certificate', 'is_active' => 1,
            'created_by' => $actor->id, 'updated_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectId = DB::table('subjects')->insertGetId([
            'name' => 'Môn cầu điểm', 'code' => 'SUB-GRADE-BRIDGE', 'specialization_id' => $specializationId,
            'credits' => 1, 'theory_hours' => 1, 'practice_hours' => 0, 'self_study_hours' => 0,
            'level' => 'basic', 'assessment_method' => 'exam', 'is_required' => 1, 'is_active' => 1,
            'created_by' => $actor->id, 'updated_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Lớp cầu điểm', 'code' => 'CLS-GRADE-BRIDGE', 'specialization_id' => $specializationId,
            'instructor_id' => $instructor->id, 'start_date' => now(), 'end_date' => now()->addMonth(),
            'duration_months' => 1, 'management_unit' => 'Test', 'max_students' => 30, 'current_students' => 1,
            'is_active' => 1, 'created_by' => $actor->id, 'updated_by' => $actor->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = User::factory()->create(['class_id' => $classId, 'user_type' => 'student', 'status' => 1]);
        $course = LmsCourse::query()->create([
            'title' => 'Khóa cầu điểm', 'subject_id' => $subjectId, 'class_id' => $classId,
            'instructor_id' => $instructor->id, 'status' => 'published', 'created_by' => $actor->id,
        ]);
        LmsCourseMember::query()->create([
            'lms_course_id' => $course->id, 'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT, 'source' => 'class', 'status' => 'active',
        ]);
        LmsGradebookRow::query()->create([
            'lms_course_id' => $course->id, 'user_id' => $student->id,
            'computed_score' => 8.25, 'final_score' => 8.25, 'letter' => 'B',
        ]);

        $book = app(LmsGradeTransferService::class)->transfer($course, $actor);
        $this->assertSame(GradeBook::STATUS_DRAFT, $book->status);
        $this->assertDatabaseHas('grade_columns', ['grade_book_id' => $book->id, 'code' => 'lms_total', 'source' => 'lms', 'is_locked' => 1]);
        $this->assertDatabaseHas('grade_cells', ['grade_book_id' => $book->id, 'user_id' => $student->id, 'score' => 8.3]);

        $book->update(['status' => GradeBook::STATUS_APPROVED]);
        LmsGradebookRow::query()->where('lms_course_id', $course->id)->where('user_id', $student->id)->update(['final_score' => 9]);
        $this->expectException(\RuntimeException::class);
        app(LmsGradeTransferService::class)->transfer($course, $actor);
    }
}
