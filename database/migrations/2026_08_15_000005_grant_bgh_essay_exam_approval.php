<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo([
                'essay-exams.approval.view',
                'essay-exams.approval.approve',
            ]);
        }
    }

    public function down(): void
    {
        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo([
                'essay-exams.approval.view',
                'essay-exams.approval.approve',
            ]);
        }
    }
};
