<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Specialization\Models\Specialization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / C1 — wizard tạo khóa phải nêu rõ sẽ ghi danh bao nhiêu học
 * viên trước khi tạo, thay vì chỉ hứa chung chung "tự đồng bộ".
 */
class LmsCourseWizardStudentCountTest extends TestCase
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

    public function test_class_student_count_endpoint_counts_only_students_of_that_class(): void
    {
        $admin = $this->superAdmin();
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Count Test', 'code' => 'CNT-'.uniqid(),
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        $classA = ClassModel::query()->create([
            'name' => 'Lớp Count A', 'code' => 'CNT-A-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $instructor->id,
            'start_date' => now(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $classB = ClassModel::query()->create([
            'name' => 'Lớp Count B', 'code' => 'CNT-B-'.uniqid(),
            'specialization_id' => $specialization->id, 'instructor_id' => $instructor->id,
            'start_date' => now(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.102', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        // 3 học viên lớp A, 1 học viên lớp B, 1 GV nội bộ lớp A (không được tính).
        User::factory()->count(3)->create(['class_id' => $classA->id, 'user_type' => 'student']);
        User::factory()->create(['class_id' => $classB->id, 'user_type' => 'student']);
        User::factory()->create(['class_id' => $classA->id, 'user_type' => 'internal_user']);

        $response = $this->actingAs($admin)
            ->getJson(route('lms.courses.class-student-count', ['class_id' => $classA->id]));

        $response->assertOk()->assertJson(['count' => 3]);
    }
}
