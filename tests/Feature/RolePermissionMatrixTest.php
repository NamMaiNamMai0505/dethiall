<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Support\PermissionMatrix;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Ma trận phân quyền phải liệt kê đầy đủ ứng dụng theo phân hệ và cho phép tick
 * lẻ từng chức năng — đúng "Bảng phân quyền vai trò".
 */
class RolePermissionMatrixTest extends TestCase
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_matrix_lists_every_subsystem_and_application(): void
    {
        $matrix = PermissionMatrix::build();

        $this->assertCount(count(ApplicationRegistry::subsystems()), $matrix['subsystems']);

        $listed = [];
        foreach ($matrix['subsystems'] as $subsystem) {
            foreach ($subsystem['applications'] as $application) {
                $listed[] = $application['key'];
            }
        }

        $this->assertEqualsCanonicalizing(
            array_keys(ApplicationRegistry::applications()),
            $listed,
            'Ma trận phải liệt kê đủ mọi ứng dụng đã khai báo.'
        );
    }

    public function test_matrix_marks_unavailable_actions_instead_of_offering_a_checkbox(): void
    {
        $matrix = PermissionMatrix::build();
        $reports = null;

        foreach ($matrix['subsystems'] as $subsystem) {
            foreach ($subsystem['applications'] as $application) {
                if ($application['key'] === 'standard-hours.reports') {
                    $reports = $application;
                }
            }
        }

        $this->assertNotNull($reports);
        $this->assertNull($reports['cells'][ApplicationRegistry::ACTION_CREATE]);
        $this->assertNull($reports['cells'][ApplicationRegistry::ACTION_DELETE]);
        $this->assertNotNull($reports['cells'][ApplicationRegistry::ACTION_EXPORT]);
    }

    public function test_matrix_reflects_what_a_role_already_holds(): void
    {
        $matrix = PermissionMatrix::build(['standard-hours.object-types.view']);

        foreach ($matrix['subsystems'] as $subsystem) {
            foreach ($subsystem['applications'] as $application) {
                if ($application['key'] !== 'standard-hours.object-types') {
                    continue;
                }

                $this->assertTrue($application['cells'][ApplicationRegistry::ACTION_VIEW]['granted']);
                $this->assertFalse($application['cells'][ApplicationRegistry::ACTION_CREATE]['granted']);
            }
        }
    }

    public function test_saving_the_matrix_grants_only_the_ticked_cells(): void
    {
        $admin = $this->superAdmin();
        $role = Role::findOrCreate('test-khoa-abc', 'web');

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'name' => 'test-khoa-abc',
                'abilities' => [
                    'standard-hours.object-types' => [ApplicationRegistry::ACTION_VIEW],
                    'standard-hours.department-overtime' => [
                        ApplicationRegistry::ACTION_VIEW,
                        ApplicationRegistry::ACTION_CREATE,
                        ApplicationRegistry::ACTION_EDIT,
                        ApplicationRegistry::ACTION_DELETE,
                    ],
                ],
            ])
            ->assertRedirect();

        $granted = $role->fresh()->permissions->pluck('name');

        $this->assertTrue($granted->contains('standard-hours.object-types.view'));
        $this->assertFalse($granted->contains('standard-hours.object-types.create'));
        $this->assertTrue($granted->contains('standard-hours.department-overtime.delete'));
        $this->assertFalse($granted->contains('standard-hours.positions.view'));
    }

    public function test_write_action_automatically_carries_the_view_permission(): void
    {
        $admin = $this->superAdmin();
        $role = Role::findOrCreate('test-chi-tick-them', 'web');

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'name' => 'test-chi-tick-them',
                'abilities' => [
                    'standard-hours.positions' => [ApplicationRegistry::ACTION_CREATE],
                ],
            ])
            ->assertRedirect();

        $granted = $role->fresh()->permissions->pluck('name');

        $this->assertTrue($granted->contains('standard-hours.positions.create'));
        $this->assertTrue(
            $granted->contains('standard-hours.positions.view'),
            'Tick Thêm phải kèm Xem, nếu không vai trò không mở được màn hình nào.'
        );
    }

    public function test_unticking_everything_revokes_the_role_permissions(): void
    {
        $admin = $this->superAdmin();
        $role = Role::findOrCreate('test-thu-hoi', 'web');
        $role->syncPermissions(
            Permission::query()->whereIn('name', [
                'standard-hours.object-types.view',
                'standard-hours.object-types.create',
            ])->get()
        );

        $this->actingAs($admin)
            ->put(route('roles.update', $role), ['name' => 'test-thu-hoi'])
            ->assertRedirect();

        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_faculty_manager_default_matrix_matches_the_approved_table(): void
    {
        $granted = RoleCatalog::permissionNames(RoleCatalog::FACULTY_MANAGER);

        // Danh mục: chỉ được Xem.
        $this->assertContains('standard-hours.object-types.view', $granted);
        $this->assertNotContains('standard-hours.object-types.create', $granted);
        $this->assertContains('standard-hours.positions.view', $granted);
        $this->assertNotContains('standard-hours.positions.edit', $granted);

        // Nghiệp vụ của khoa: đủ Xem/Thêm/Sửa/Xóa.
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $this->assertContains("standard-hours.department-overtime.{$action}", $granted);
            $this->assertContains("standard-hours.norm-reductions.{$action}", $granted);
        }

        // Không được đụng cấu hình hệ thống.
        $this->assertNotContains('roles.edit', $granted);
        $this->assertNotContains('standard-hours.settings.period-mode.edit', $granted);
    }

    public function test_role_groups_are_created_with_their_default_matrix(): void
    {
        foreach ([RoleCatalog::FACULTY_MANAGER, RoleCatalog::RESEARCH_AGENCY_MANAGER] as $name) {
            $role = Role::query()->where('name', $name)->where('guard_name', 'web')->first();

            $this->assertNotNull($role, "Migration phải tạo vai trò {$name}.");
            $this->assertNotEmpty(
                $role->permissions,
                "Vai trò {$name} phải được cấp ma trận mặc định."
            );
        }
    }
}
