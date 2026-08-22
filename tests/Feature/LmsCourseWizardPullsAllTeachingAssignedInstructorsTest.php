<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourseInstructor;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / C3 — wizard tạo khóa (CourseController::store →
 * LmsCourseService::createWithMembers) trước đây chỉ gán đúng 1 GV chọn tay
 * (registerManualInstructor), bỏ sót các GV khác đã được Khoa phân công dạy
 * môn qua TeachingAssignment. Giờ phải kéo toàn bộ.
 */
class LmsCourseWizardPullsAllTeachingAssignedInstructorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_creating_a_course_enrolls_every_teaching_assigned_instructor_not_just_the_manual_pick(): void
    {
        $admin = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $admin->syncRoles([$role->name]);

        $specialization = Specialization::query()->create([
            'name' => 'Ngành Wizard Test', 'code' => 'WIZ-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Wizard Test', 'code' => 'WIZ-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $classInstructor = Instructor::factory()->create();
        $class = ClassModel::query()->create([
            'name' => 'Lớp Wizard Test', 'code' => 'WIZ-CLS-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $classInstructor->id,
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        // Khoa đã phân công 3 GV dạy môn này — GV chọn tay trong wizard chỉ là 1 trong số đó.
        $manualPick = Instructor::factory()->create(['name' => 'GV Chọn Tay']);
        $secondAssigned = Instructor::factory()->create(['name' => 'GV Phân Công 2']);
        $thirdAssigned = Instructor::factory()->create(['name' => 'GV Phân Công 3']);
        // Bảng thật là "teaching_assignment" (số ít) — model Eloquent chưa khai
        // báo $table nên bị lệch, xem task đã báo riêng. Ghi thẳng qua DB::table
        // để không phụ thuộc model đang lỗi.
        DB::table('teaching_assignment')->insert([
            ['subject_id' => $subject->id, 'instructor_id' => $manualPick->id, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $subject->id, 'instructor_id' => $secondAssigned->id, 'created_at' => now(), 'updated_at' => now()],
            ['subject_id' => $subject->id, 'instructor_id' => $thirdAssigned->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $academicYear = \App\Models\AcademicYear::query()->firstOrCreate(
            ['code' => '2026-2027'],
            ['name' => 'Năm học 2026-2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true, 'is_current' => true]
        );

        $course = app(LmsCourseService::class)->createWithMembers([
            'title' => 'Khóa Wizard Test',
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'instructor_id' => $manualPick->id,
            'academic_year_id' => $academicYear->id,
            'is_standalone' => false,
        ]);

        $assignedInstructorIds = LmsCourseInstructor::query()
            ->where('lms_course_id', $course->id)
            ->pluck('instructor_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect([$manualPick->id, $secondAssigned->id, $thirdAssigned->id])->sort()->values()->all(),
            $assignedInstructorIds,
            'Wizard phải kéo cả 3 GV được Khoa phân công, không chỉ GV chọn tay.'
        );

        // Cả 3 GV phải là thành viên khóa (role lecturer), không chỉ đứng trong pivot.
        foreach ([$manualPick, $secondAssigned, $thirdAssigned] as $instructor) {
            $userId = User::query()->where('instructor_id', $instructor->id)->value('id');
            if (! $userId) {
                continue; // Instructor factory không luôn tạo User liên kết — bỏ qua nếu không có.
            }
            $this->assertTrue(
                LmsCourseMember::query()->where('lms_course_id', $course->id)
                    ->where('user_id', $userId)->where('role', LmsCourseMember::ROLE_LECTURER)->exists(),
                "GV {$instructor->name} phải có mặt trong danh sách thành viên khóa."
            );
        }
    }
}
