<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Lms\Models\LmsAttendanceSession;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / C4 — điểm danh mặc định "có mặt"; giảng viên chỉ gạt người
 * vắng, không phải gạt từng người có mặt trong lớp đông.
 */
class LmsAttendanceDefaultPresentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function superAdmin(): User
    {
        $role = Role::findOrCreate(ManagementRole::SUPER_ADMIN, 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $user = User::factory()->create(['user_type' => 'internal_user', 'status' => 1]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['roles']);
    }

    public function test_attendance_session_page_defaults_every_student_to_present(): void
    {
        $admin = $this->superAdmin();
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Attendance Test', 'code' => 'ATT-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $subject = Subject::query()->create([
            'name' => 'Môn Attendance Test', 'code' => 'ATT-SUBJ-'.uniqid(),
            'specialization_id' => $specialization->id, 'credits' => 2, 'theory_hours' => 20,
            'practice_hours' => 0, 'self_study_hours' => 0, 'exam_hours' => 0, 'level' => 'basic',
            'assessment_method' => 'exam', 'is_required' => true, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        $class = ClassModel::query()->create([
            'name' => 'Lớp Attendance Test', 'code' => 'ATT-CLS-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $instructor->id,
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(),
            'duration_months' => 12, 'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101',
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $course = LmsCourse::query()->create([
            'title' => 'Khóa Attendance Test',
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $student = User::factory()->create(['name' => 'Học viên Chưa Điểm Danh']);
        LmsCourseMember::query()->create([
            'lms_course_id' => $course->id,
            'user_id' => $student->id,
            'role' => LmsCourseMember::ROLE_STUDENT,
        ]);
        $session = LmsAttendanceSession::query()->create([
            'lms_course_id' => $course->id,
            'title' => 'Buổi 1',
            'session_date' => now()->toDateString(),
            'mode' => 'manual',
            'status' => 'open',
            'open_from' => now(),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('lms.courses.attendance.show', [$course, $session]));

        $response->assertOk();
        // Học viên chưa có bản ghi điểm danh (chưa check-in, GV chưa gạt tay)
        // phải mặc định "có mặt" trong <select>, không phải "vắng".
        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<option value="present" selected>Có mặt<\/option>/',
            $html,
            'Option "Có mặt" phải mang thuộc tính selected khi chưa có bản ghi điểm danh.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<option value="absent" selected>Vắng<\/option>/',
            $html,
            'Option "Vắng" không còn là mặc định.'
        );
    }
}
