<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $role = Role::where('name', 'ban giám hiệu')->where('guard_name', 'web')->first();
        if (!$role) {
            return;
        }

        $role->givePermissionTo([
            'essay-exams.index',
            'essay-exams.approval.view',
            'essay-exams.approval.approve',
            'essay-exams.bank.view',
            'essay-exams.draw.view',
        ]);
        $role->revokePermissionTo([
            'essay-exams.authoring.create',
            'essay-exams.import.create',
            'essay-exams.submission.create',
            'essay-exams.bank.create', 'essay-exams.bank.edit', 'essay-exams.bank.delete', 'essay-exams.bank.approve', 'essay-exams.bank.export',
            'essay-exams.draw.create', 'essay-exams.draw.edit', 'essay-exams.draw.delete', 'essay-exams.draw.export',
        ]);
    }

    public function down(): void
    {
        $role = Role::where('name', 'ban giám hiệu')->where('guard_name', 'web')->first();
        $role?->revokePermissionTo([
            'essay-exams.index',
            'essay-exams.approval.view',
            'essay-exams.approval.approve',
            'essay-exams.bank.view',
            'essay-exams.draw.view',
        ]);
    }
};
