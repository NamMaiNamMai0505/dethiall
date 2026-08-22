<?php

use App\Support\ManagementRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Tên vai trò cố định tại thời điểm migration này chạy. Không dùng
        // ManagementRole::scopedRoles() vì danh sách đó thay đổi theo thời gian,
        // migration lịch sử phải chạy được với mọi phiên bản code về sau.
        $scopedRoles = [
            ManagementRole::SYSTEM_MANAGER,
            ManagementRole::TRAINING_OFFICE_MANAGER,
            'faculty-schedule-manager',
            'standard-hours-manager',
        ];

        foreach ($scopedRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        foreach ([
            ManagementRole::TRAINING_OFFICE_MANAGER,
            'faculty-schedule-manager',
            'standard-hours-manager',
        ] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            $permissions = collect(ManagementRole::permissionNames($roleName))
                ->map(fn (string $permission) => Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]));

            $role->syncPermissions($permissions);
        }

        $systemPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->get()
            ->filter(fn (Permission $permission) => ManagementRole::systemManagerMayReceive($permission->name));

        Role::query()->where('name', ManagementRole::SYSTEM_MANAGER)->where('guard_name', 'web')
            ->first()?->syncPermissions($systemPermissions);

        $superAdmin = Role::query()
            ->where('name', ManagementRole::SUPER_ADMIN)
            ->where('guard_name', 'web')
            ->first();
        $superAdmin?->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $scopedRoles = [
            ManagementRole::SYSTEM_MANAGER,
            ManagementRole::TRAINING_OFFICE_MANAGER,
            'faculty-schedule-manager',
            'standard-hours-manager',
        ];

        if (! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()
            ->whereIn('name', $scopedRoles)
            ->get()
            ->each(function (Role $role): void {
                if (! $role->users()->exists()) {
                    $role->delete();
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
