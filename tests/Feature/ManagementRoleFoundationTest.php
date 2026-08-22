<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ManagementRole;
use App\Support\RoleAssignment;
use App\Support\TrainingScheduleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManagementRoleFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_migrations_create_unit_scope_fields_and_dedicated_roles(): void
    {
        $this->assertTrue(Schema::hasColumns('units', ['functional_type', 'faculty_code']));

        foreach (ManagementRole::scopedRoles() as $roleName) {
            $this->assertDatabaseHas('roles', [
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_dedicated_roles_have_separated_permissions(): void
    {
        $trainingOffice = Role::findByName(ManagementRole::TRAINING_OFFICE_MANAGER);
        $faculty = Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER);
        $system = Role::findByName(ManagementRole::SYSTEM_MANAGER);

        $this->assertTrue($trainingOffice->hasPermissionTo('training-schedules.edit'));
        $this->assertFalse($trainingOffice->hasPermissionTo('standard-hours.approve'));

        $this->assertTrue($faculty->hasPermissionTo('schedule-details.edit'));
        $this->assertFalse($faculty->hasPermissionTo('training-schedules.edit'));

        // Giờ chuẩn của khoa: làm được nghiệp vụ khoa, không đụng danh mục toàn trường.
        $this->assertTrue($faculty->hasPermissionTo('standard-hours.department-overtime.delete'));
        $this->assertFalse($faculty->hasPermissionTo('standard-hours.object-types.create'));

        // Quản lý toàn trường quản trị trọn phân hệ Giờ chuẩn.
        $this->assertTrue($system->hasPermissionTo('standard-hours.object-types.create'));
        $this->assertTrue($system->hasPermissionTo('standard-hours.calculations.run'));

        $this->assertFalse($system->permissions->contains('name', 'roles.edit'));
        $this->assertFalse($system->permissions->contains('name', 'standard-hours.override-approved'));
    }

    public function test_training_schedule_scope_requires_both_role_and_matching_unit(): void
    {
        $trainingUnit = Unit::create([
            'code' => 'PDT-TEST',
            'name' => 'Phòng Đào tạo Test',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $facultyUnit = Unit::create([
            'code' => 'FAC-TEST',
            'name' => 'Khoa Test',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K1',
            'status' => Unit::STATUS_ACTIVE,
        ]);

        $trainingRole = Role::findByName(ManagementRole::TRAINING_OFFICE_MANAGER);
        $facultyRole = Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER);

        $trainingUser = $this->userWithRole($trainingRole, $trainingUnit);
        $facultyUser = $this->userWithRole($facultyRole, $facultyUnit);
        $mismatchedUser = $this->userWithRole($facultyRole, $trainingUnit);

        $this->assertSame(TrainingScheduleAccess::SCOPE_TRAINING_OFFICE, TrainingScheduleAccess::scope($trainingUser));
        $this->assertTrue(TrainingScheduleAccess::canManageSkeleton($trainingUser));
        $this->assertFalse(TrainingScheduleAccess::canAssignFacultySchedule($trainingUser));

        $this->assertSame(TrainingScheduleAccess::SCOPE_FACULTY, TrainingScheduleAccess::scope($facultyUser));
        $this->assertSame('K1', TrainingScheduleAccess::facultyCode($facultyUser));
        $this->assertTrue(TrainingScheduleAccess::canAssignFacultySchedule($facultyUser));
        $this->assertFalse(TrainingScheduleAccess::canManageSkeleton($facultyUser));

        $this->assertSame(TrainingScheduleAccess::SCOPE_NONE, TrainingScheduleAccess::scope($mismatchedUser));
    }

    public function test_faculty_role_accepts_legacy_k_code_until_units_are_backfilled(): void
    {
        $legacyFacultyUnit = Unit::query()->where('code', 'K7')->firstOrFail();
        $legacyFacultyUnit->forceFill([
            'functional_type' => Unit::FUNCTIONAL_OTHER,
            'faculty_code' => null,
        ])->save();
        $facultyRole = Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER);
        $facultyUser = $this->userWithRole($facultyRole, $legacyFacultyUnit);

        $this->assertSame(TrainingScheduleAccess::SCOPE_FACULTY, TrainingScheduleAccess::scope($facultyUser));
        $this->assertSame('K7', TrainingScheduleAccess::facultyCode($facultyUser));
        $this->assertNull(RoleAssignment::roleUnitValidationError($facultyRole->id, $legacyFacultyUnit->id, 'internal_user'));
    }

    public function test_role_unit_guardrail_rejects_mismatched_management_assignment(): void
    {
        $trainingUnit = Unit::create([
            'code' => 'PDT-GUARD',
            'name' => 'Phòng Đào tạo Guard',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $facultyUnit = Unit::create([
            'code' => 'FAC-GUARD',
            'name' => 'Khoa Guard',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K2',
            'status' => Unit::STATUS_ACTIVE,
        ]);

        $trainingRole = Role::findByName(ManagementRole::TRAINING_OFFICE_MANAGER);
        $facultyRole = Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER);
        $standardRole = Role::findByName(ManagementRole::SYSTEM_MANAGER);

        $this->assertNull(RoleAssignment::roleUnitValidationError(
            $trainingRole->id,
            $trainingUnit->id,
            'internal_user'
        ));

        $this->assertSame('unit_id', RoleAssignment::roleUnitValidationError(
            $trainingRole->id,
            $facultyUnit->id,
            'internal_user'
        )['field']);

        $this->assertSame('unit_id', RoleAssignment::roleUnitValidationError(
            $facultyRole->id,
            $trainingUnit->id,
            'internal_user'
        )['field']);

        $this->assertNull(RoleAssignment::roleUnitValidationError(
            $standardRole->id,
            null,
            'internal_user'
        ));
        $this->assertSame('user_type', RoleAssignment::roleUnitValidationError(
            $standardRole->id,
            null,
            'instructor'
        )['field']);
    }

    public function test_transition_command_is_dry_run_by_default_and_apply_is_idempotent(): void
    {
        $trainingUnit = Unit::create([
            'code' => 'PHONG_DT',
            'name' => 'Phòng Đào tạo',
            'functional_type' => Unit::FUNCTIONAL_TRAINING_OFFICE,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $unknownUnit = Unit::create([
            'code' => 'OTHER-TEST',
            'name' => 'Đơn vị chưa phân loại',
            'functional_type' => Unit::FUNCTIONAL_OTHER,
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $legacyRole = Role::findOrCreate(ManagementRole::LEGACY_MANAGER, 'web');

        $convertible = $this->userWithRole($legacyRole, $trainingUnit);
        $unresolved = $this->userWithRole($legacyRole, $unknownUnit);

        $this->artisan('management-roles:transition')
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();
        $this->assertTrue($convertible->fresh()->hasRole(ManagementRole::LEGACY_MANAGER));

        $this->artisan('management-roles:transition', ['--apply' => true])
            ->assertSuccessful();
        $this->assertTrue($convertible->fresh()->hasRole(ManagementRole::TRAINING_OFFICE_MANAGER));
        $this->assertFalse($convertible->fresh()->hasRole(ManagementRole::LEGACY_MANAGER));
        $this->assertTrue($unresolved->fresh()->hasRole(ManagementRole::LEGACY_MANAGER));

        $this->artisan('management-roles:transition', ['--apply' => true])
            ->assertSuccessful();
        $this->assertTrue($convertible->fresh()->hasRole(ManagementRole::TRAINING_OFFICE_MANAGER));
    }

    public function test_transition_does_not_promote_non_manager_from_stale_role_id(): void
    {
        $facultyUnit = Unit::create([
            'code' => 'FAC-STALE',
            'name' => 'Khoa Stale Role',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K4',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $legacyRole = Role::findOrCreate(ManagementRole::LEGACY_MANAGER, 'web');
        $instructorRole = Role::findOrCreate('instructor', 'web');
        $instructor = User::factory()->create([
            'unit_id' => $facultyUnit->id,
            'role_id' => $legacyRole->id,
            'user_type' => 'instructor',
        ]);
        $instructor->syncRoles([$instructorRole->name]);

        $this->artisan('management-roles:transition', ['--apply' => true])
            ->assertSuccessful();

        $instructor->refresh();
        $this->assertTrue($instructor->hasRole('instructor'));
        $this->assertFalse($instructor->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER));
        $this->assertSame($legacyRole->id, $instructor->role_id);
    }

    public function test_role_id_only_transition_requires_explicit_option_and_internal_user(): void
    {
        $facultyUnit = Unit::create([
            'code' => 'FAC-ROLE-ID',
            'name' => 'Khoa Role ID',
            'functional_type' => Unit::FUNCTIONAL_FACULTY,
            'faculty_code' => 'K5',
            'status' => Unit::STATUS_ACTIVE,
        ]);
        $legacyRole = Role::findOrCreate(ManagementRole::LEGACY_MANAGER, 'web');
        $internalUser = User::factory()->create([
            'unit_id' => $facultyUnit->id,
            'role_id' => $legacyRole->id,
            'user_type' => 'internal_user',
        ]);

        $this->artisan('management-roles:transition', ['--apply' => true])
            ->assertSuccessful();
        $this->assertFalse($internalUser->fresh()->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER));

        $this->artisan('management-roles:transition', [
            '--apply' => true,
            '--include-role-id-only' => true,
            '--user' => [(string) $internalUser->id],
        ])->assertSuccessful();

        $internalUser->refresh();
        $this->assertTrue($internalUser->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER));
        $this->assertFalse($internalUser->hasRole(ManagementRole::LEGACY_MANAGER));
        $this->assertSame(
            Role::findByName(ManagementRole::FACULTY_SCHEDULE_MANAGER, 'web')->id,
            $internalUser->role_id
        );
    }

    private function userWithRole(Role $role, Unit $unit): User
    {
        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role_id' => $role->id,
            'user_type' => 'internal_user',
        ]);
        $user->syncRoles([$role->name]);

        return $user->fresh(['unit', 'roles']);
    }
}
