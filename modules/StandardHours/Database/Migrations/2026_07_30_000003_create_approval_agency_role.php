<?php

use App\Support\ApprovalAgency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const BASE_PERMISSIONS = [
        'dashboards.index',
        'standard-hours.index',
        'standard-hours.show',
        'standard-hours.approve',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::BASE_PERMISSIONS)
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $approvalRole = Role::firstOrCreate([
            'name' => ApprovalAgency::ROLE,
            'guard_name' => 'web',
        ]);
        $approvalRole->syncPermissions($permissions);

        // Giữ tương thích cho các tài khoản quản lý đã có trước khi tách
        // standard-hours.approve khỏi standard-hours.edit.
        $approvePermission = $permissions->firstWhere('name', 'standard-hours.approve');
        Role::query()
            ->whereIn('name', ['manager', 'exam-manager'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($approvePermission));

        Role::query()
            ->where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo(Permission::query()->where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', ApprovalAgency::ROLE)
            ->where('guard_name', 'web')
            ->first();

        if ($role && $role->users()->doesntExist()) {
            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
