<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $grant = [
            'essay-exams.index',
            'essay-exams.approval.view',
            'essay-exams.approval.approve',
        ];

        $revoke = [
            'essay-exams.create', 'essay-exams.import', 'essay-exams.submit', 'essay-exams.bank', 'essay-exams.draw',
            'essay-exams.authoring.index', 'essay-exams.authoring.show', 'essay-exams.authoring.create', 'essay-exams.authoring.edit', 'essay-exams.authoring.delete',
            'essay-exams.import.index', 'essay-exams.import.show', 'essay-exams.import.create', 'essay-exams.import.edit', 'essay-exams.import.delete',
            'essay-exams.submission.view', 'essay-exams.submission.create', 'essay-exams.submission.edit', 'essay-exams.submission.delete',
            'essay-exams.bank.view', 'essay-exams.bank.create', 'essay-exams.bank.edit', 'essay-exams.bank.delete', 'essay-exams.bank.approve', 'essay-exams.bank.export',
            'essay-exams.draw.view', 'essay-exams.draw.create', 'essay-exams.draw.edit', 'essay-exams.draw.delete', 'essay-exams.draw.export',
        ];

        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            $role->givePermissionTo($grant);
            $role->revokePermissionTo($revoke);
        }
    }

    public function down(): void
    {
        foreach (['bgh', 'board-of-management'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->revokePermissionTo(['essay-exams.index']);
        }
    }
};
