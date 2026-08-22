<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ROLE = 'exam-manager';

    private const PERMISSIONS = [
        'dashboards.index',
        'instructors.index',
        'instructors.show',
        'training-schedules.index',
        'training-schedules.show',
        'schedule-details.index',
        'schedule-details.show',
        'standard-hours.index',
        'standard-hours.show',
        'standard-hours.create',
        'standard-hours.edit',
        'standard-hours.delete',
        'grades.index',
        'grades.show',
        'grades.create',
        'grades.edit',
        'grades.manage',
        'export-templates.index',
        'export-templates.show',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => self::ROLE,
            'guard_name' => 'web',
        ]);

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $role->givePermissionTo($permission);
        }

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
            ->where('name', self::ROLE)
            ->where('guard_name', 'web')
            ->first();

        if ($role && $role->users()->doesntExist()) {
            $role->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
