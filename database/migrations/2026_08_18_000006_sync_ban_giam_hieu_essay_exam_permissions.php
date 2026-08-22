<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $role = Role::where('guard_name', 'web')->get()->first(function (Role $candidate): bool {
            $name = mb_strtolower(trim((string) $candidate->name));
            return in_array($name, ['ban giám hiệu', 'ban giÃ¡m hiá»‡u'], true);
        });

        if (!$role) return;

        $role->givePermissionTo([
            'essay-exams.index',
            'essay-exams.approval.index',
            'essay-exams.approval.approve',
            'essay-exams.bank.index',
            'essay-exams.draw.index',
            'essay-exams.draw.export',
        ]);

        $role->revokePermissionTo([
            'essay-exams.authoring.create', 'essay-exams.authoring.edit', 'essay-exams.authoring.delete',
            'essay-exams.import.create', 'essay-exams.import.edit', 'essay-exams.import.delete',
            'essay-exams.submission.create', 'essay-exams.submission.edit', 'essay-exams.submission.delete',
            'essay-exams.bank.create', 'essay-exams.bank.edit', 'essay-exams.bank.delete', 'essay-exams.bank.approve',
            'essay-exams.draw.create', 'essay-exams.draw.edit', 'essay-exams.draw.delete',
        ]);
    }

    public function down(): void
    {
        // Giữ nguyên quyền đã đồng bộ để không làm mất cấu hình của role khi rollback.
    }
};
