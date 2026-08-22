<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / C2 — tạo khóa cho một lớp phải ghi danh CẢ LỚP tự động (không
 * ghi danh từng cá nhân), khớp đúng con số đã hiện trong wizard (C1).
 */
class LmsCourseEnrollsEntireClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_creating_a_course_enrolls_every_student_of_the_selected_class(): void
    {
        $admin = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $admin->syncRoles([$role->name]);

        $specialization = Specialization::query()->create([
            'name' => 'Ngành Enroll Test', 'code' => 'ENR-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Enroll Test', 'code' => 'ENR-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        $class = ClassModel::query()->create([
            'name' => 'Lớp Enroll Test', 'code' => 'ENR-CLS-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $instructor->id,
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        // 60 học viên thuộc lớp — DoD nêu đích danh kịch bản lớp đông.
        $students = User::factory()->count(60)->create(['class_id' => $class->id, 'user_type' => 'student']);

        $academicYear = AcademicYear::query()->firstOrCreate(
            ['code' => '2026-2027'],
            ['name' => 'Năm học 2026-2027', 'start_year' => 2026, 'end_year' => 2027, 'is_active' => true, 'is_current' => true]
        );

        $course = app(LmsCourseService::class)->createWithMembers([
            'title' => 'Khóa Enroll Test',
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $academicYear->id,
            'is_standalone' => false,
        ]);

        $enrolledStudentIds = LmsCourseMember::query()
            ->where('lms_course_id', $course->id)
            ->where('role', LmsCourseMember::ROLE_STUDENT)
            ->pluck('user_id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(60, count($enrolledStudentIds), 'Phải ghi danh đúng 60 học viên — bằng số học viên của lớp.');
        $this->assertSame(
            $students->pluck('id')->sort()->values()->all(),
            $enrolledStudentIds,
            'Phải ghi danh đúng những học viên thuộc lớp, không thiếu không thừa.'
        );
    }
}
