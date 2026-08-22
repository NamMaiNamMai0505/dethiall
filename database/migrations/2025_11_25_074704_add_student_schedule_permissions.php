<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'student-schedule.index',
            'student-schedule.show',
            'student-schedule.create',
            'student-schedule.edit',
            'student-schedule.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $studentRole->givePermissionTo([
                'student-schedule.index',
                'student-schedule.show',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'student-schedule.index',
            'student-schedule.show',
            'student-schedule.create',
            'student-schedule.edit',
            'student-schedule.delete',
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
