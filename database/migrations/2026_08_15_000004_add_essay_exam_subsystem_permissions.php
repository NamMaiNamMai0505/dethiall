<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            'essay-exams.authoring.index', 'essay-exams.authoring.show', 'essay-exams.authoring.create', 'essay-exams.authoring.edit', 'essay-exams.authoring.delete',
            'essay-exams.import.index', 'essay-exams.import.show', 'essay-exams.import.create', 'essay-exams.import.edit', 'essay-exams.import.delete',
            'essay-exams.submission.view', 'essay-exams.submission.create', 'essay-exams.submission.edit', 'essay-exams.submission.delete',
            'essay-exams.approval.view', 'essay-exams.approval.create', 'essay-exams.approval.edit', 'essay-exams.approval.delete', 'essay-exams.approval.approve',
            'essay-exams.bank.view', 'essay-exams.bank.create', 'essay-exams.bank.edit', 'essay-exams.bank.delete', 'essay-exams.bank.approve', 'essay-exams.bank.export',
            'essay-exams.draw.view', 'essay-exams.draw.create', 'essay-exams.draw.edit', 'essay-exams.draw.delete', 'essay-exams.draw.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'like', 'essay-exams.%')->delete();
    }
};
