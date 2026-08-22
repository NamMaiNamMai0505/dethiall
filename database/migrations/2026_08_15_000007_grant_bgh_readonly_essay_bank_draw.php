<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            $role->givePermissionTo([
                'essay-exams.bank.view',
                'essay-exams.draw.view',
            ]);
            $role->revokePermissionTo([
                'essay-exams.bank.create', 'essay-exams.bank.edit', 'essay-exams.bank.delete', 'essay-exams.bank.approve', 'essay-exams.bank.export',
                'essay-exams.draw.create', 'essay-exams.draw.edit', 'essay-exams.draw.delete', 'essay-exams.draw.export',
                'essay-exams.bank', 'essay-exams.draw',
            ]);
        }
    }

    public function down(): void
    {
        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo(['essay-exams.bank.view', 'essay-exams.draw.view']);
        }
    }
};
