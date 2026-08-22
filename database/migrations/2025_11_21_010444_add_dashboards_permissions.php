<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration này thêm permissions cho Dashboard module
     * và gán chúng cho các roles tương ứng.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo permissions cho dashboards module
        $permissions = [
            'dashboards.index',
            'dashboards.show',
            'dashboards.create',
            'dashboards.edit',
            'dashboards.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Gán tất cả permissions cho super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::all();
            $superAdminRole->syncPermissions($allPermissions);
        }

    }

    /**
     * Reverse the migrations.
     *
     * Rollback: Xóa permissions của dashboards module
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Xóa permissions
        $permissions = [
            'dashboards.index',
            'dashboards.show',
            'dashboards.create',
            'dashboards.edit',
            'dashboards.delete',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission) {
                $permission->delete();
            }
        }
    }
};
