<?php

namespace Tests\Feature;

use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApplicationRegistryPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_every_declared_application_has_a_view_action_and_a_label(): void
    {
        foreach (ApplicationRegistry::applications() as $key => $application) {
            $this->assertNotSame('', trim($application['label']), "Ứng dụng {$key} thiếu nhãn nghiệp vụ.");
            $this->assertArrayHasKey(
                ApplicationRegistry::ACTION_VIEW,
                $application['actions'],
                "Ứng dụng {$key} phải có hành động Xem để hiển thị trong ma trận."
            );
        }
    }

    public function test_migration_creates_every_permission_declared_by_the_registry(): void
    {
        $declared = ApplicationRegistry::permissionNames();
        $existing = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $this->assertSame(
            [],
            array_values(array_diff($declared, $existing)),
            'Có permission khai báo trong registry nhưng chưa được migration tạo.'
        );
    }

    public function test_standard_hours_applications_expose_granular_create_edit_delete(): void
    {
        $catalogApplications = [
            'standard-hours.object-types',
            'standard-hours.positions',
            'standard-hours.departments',
            'standard-hours.department-overtime',
            'standard-hours.norm-reductions',
            'standard-hours.conversion-categories',
            'standard-hours.research-categories',
            'standard-hours.hour-exchanges',
        ];

        foreach ($catalogApplications as $key) {
            foreach ([
                ApplicationRegistry::ACTION_CREATE,
                ApplicationRegistry::ACTION_EDIT,
                ApplicationRegistry::ACTION_DELETE,
            ] as $action) {
                $this->assertNotEmpty(
                    ApplicationRegistry::permissionNamesFor($key, $action),
                    "Ứng dụng {$key} thiếu hành động {$action} trong ma trận."
                );
            }
        }
    }

    public function test_legacy_manage_permission_is_expanded_for_the_school_manager(): void
    {
        $role = Role::findByName(ManagementRole::SYSTEM_MANAGER, 'web');
        $granted = $role->permissions->pluck('name');

        // Role cũ chỉ có `.manage`; sau migration phải có đủ Thêm/Sửa/Xóa.
        foreach ([
            'standard-hours.object-types.create',
            'standard-hours.object-types.edit',
            'standard-hours.object-types.delete',
            'standard-hours.settings.period-mode.view',
            'standard-hours.settings.research-rules.view',
        ] as $permission) {
            $this->assertTrue(
                $granted->contains($permission),
                "Quản lý toàn trường bị mất quyền {$permission} sau khi tách quyền chi tiết."
            );
        }

        // Quyền gộp cũ vẫn giữ để role/khoá cấu hình cũ không gãy.
        $this->assertTrue($granted->contains('standard-hours.object-types.manage'));
    }

    public function test_reports_application_stays_read_only(): void
    {
        foreach ([
            ApplicationRegistry::ACTION_CREATE,
            ApplicationRegistry::ACTION_EDIT,
            ApplicationRegistry::ACTION_DELETE,
        ] as $action) {
            $this->assertSame(
                [],
                ApplicationRegistry::permissionNamesFor('standard-hours.reports', $action),
                'Báo cáo thống kê chỉ đọc — không được sinh quyền ghi.'
            );
        }

        $this->assertSame(
            ['standard-hours.reports.export'],
            ApplicationRegistry::permissionNamesFor('standard-hours.reports', ApplicationRegistry::ACTION_EXPORT)
        );
    }
}
