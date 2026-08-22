<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'essay-exams.bank',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'instructor')
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'essay-exams.bank')
            ->where('guard_name', 'web')
            ->first();

        if (!$permission) {
            return;
        }

        Role::where('name', 'instructor')
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));
    }
};
