<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Specialization\Models\Specialization;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sprint 44 / B2 — form Sửa lịch đào tạo phải lọc Lớp theo Ngành bằng cơ chế
 * tương thích TomSelect (setTomSelectOptions), giống create.blade.php.
 * Bản cũ ghi thẳng innerHTML nên không đồng bộ với TomSelect và không lọc
 * ngay khi vừa mở trang sửa (ngành đã có sẵn nhưng lớp vẫn hiện hết).
 */
class TrainingScheduleClassCascadeTest extends TestCase
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

    public function test_edit_form_ships_a_tomselect_safe_class_cascade_with_specialization_ids(): void
    {
        $admin = $this->superAdmin();
        $specialization = Specialization::query()->create([
            'name' => 'Ngành Cascade Schedule',
            'code' => 'SCH-CASCADE-'.$admin->id,
            'level' => Specialization::LEVEL_ADVANCED,
            'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        $class = ClassModel::query()->create([
            'name' => 'Lớp Cascade Schedule',
            'code' => 'CLS-CASCADE-'.$admin->id,
            'specialization_id' => $specialization->id,
            'instructor_id' => $instructor->id,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(10),
            'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo',
            'classroom' => 'P.101',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $schedule = TrainingSchedule::query()->create([
            'name' => 'Lịch Cascade Schedule',
            'code' => 'SCHED-CASCADE-'.$admin->id,
            'specialization_id' => $specialization->id,
            'class_code' => $class->code,
            'academic_year' => '2026-2027',
            'semester' => 'semester_1',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('training-schedules.edit', $schedule));

        $response->assertOk()
            // Rebuild qua setTomSelectOptions, không ghi thẳng innerHTML nữa.
            ->assertSee('setTomSelectOptions', false)
            // Danh sách lớp cho JS phải mang theo specialization_id để lọc local.
            ->assertSee('"specialization_id":"'.$specialization->id.'"', false)
            // Thuộc tính data-specialization-id kiểu cũ (không tương thích TomSelect) đã bị bỏ.
            ->assertDontSee('data-specialization-id', false);
    }

    public function test_classes_api_filters_by_specialization(): void
    {
        $admin = $this->superAdmin();
        $specA = Specialization::query()->create([
            'name' => 'Ngành A', 'code' => 'API-A-'.$admin->id,
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $specB = Specialization::query()->create([
            'name' => 'Ngành B', 'code' => 'API-B-'.$admin->id,
            'level' => Specialization::LEVEL_ADVANCED, 'duration_months' => 36,
            'certification_type' => Specialization::CERTIFICATION_CERTIFICATE,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $instructor = Instructor::factory()->create();
        ClassModel::query()->create([
            'name' => 'Lớp A', 'code' => 'CLS-A-'.$admin->id, 'specialization_id' => $specA->id,
            'instructor_id' => $instructor->id,
            'start_date' => now(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.101', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        ClassModel::query()->create([
            'name' => 'Lớp B', 'code' => 'CLS-B-'.$admin->id, 'specialization_id' => $specB->id,
            'instructor_id' => $instructor->id,
            'start_date' => now(), 'end_date' => now()->addYear(), 'duration_months' => 12,
            'management_unit' => 'Phòng Đào tạo', 'classroom' => 'P.102', 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('training-schedules.api.classes', ['specialization_id' => $specA->id]));

        $response->assertOk()->assertJsonCount(1)->assertJsonFragment(['name' => 'Lớp A']);
    }
}
