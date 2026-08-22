<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration này thêm permissions cho InstructorSchedule module
     * và gán chúng cho các roles tương ứng.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo permissions cho instructor-schedule module
        $permissions = [
            'instructor-schedule.index',
            'instructor-schedule.show',
            'instructor-schedule.create',
            'instructor-schedule.edit',
            'instructor-schedule.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Gán permissions cho instructor role (chỉ index và show)
        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole) {
            $instructorRole->givePermissionTo([
                'instructor-schedule.index',
                'instructor-schedule.show',
            ]);
        }

        // Gán tất cả permissions cho super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::all();
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Gán tất cả permissions (trừ manage.*) cho admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = Permission::where('name', 'not like', 'manage.%')->get();
            $adminRole->syncPermissions($adminPermissions);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Rollback: Xóa permissions của instructor-schedule module
     */
    public function down(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Xóa permissions
        $permissions = [
            'instructor-schedule.index',
            'instructor-schedule.show',
            'instructor-schedule.create',
            'instructor-schedule.edit',
            'instructor-schedule.delete',
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
