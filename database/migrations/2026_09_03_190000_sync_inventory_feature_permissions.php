<?php

use App\Support\ApplicationRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = now();
        $permissions = ApplicationRegistry::subsystemPermissionNames('inventory-management');

        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        $legacyPermissionIds = DB::table('permissions')
            ->whereIn('name', [
                'inventory.index',
                'inventory.show',
                'inventory.create',
                'inventory.edit',
                'inventory.delete',
                'inventory.approve',
                'inventory.export',
                'inventory.import',
            ])
            ->where('guard_name', 'web')
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $legacyPermissionIds)
            ->pluck('role_id')
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        $directAssignments = DB::table('model_has_permissions')
            ->whereIn('permission_id', $legacyPermissionIds)
            ->select('model_type', 'model_id')
            ->distinct()
            ->get();

        foreach ($directAssignments as $assignment) {
            foreach ($permissionIds as $permissionId) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ApplicationRegistry::subsystemPermissionNames('inventory-management'))
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
