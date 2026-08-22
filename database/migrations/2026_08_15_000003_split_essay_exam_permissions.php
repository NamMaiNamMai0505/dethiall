<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $names = [
            'essay-exams.import',
            'essay-exams.submit',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $grant = function (string $role, array $permissions): void {
            if ($model = Role::where('name', $role)->where('guard_name', 'web')->first()) {
                $model->givePermissionTo($permissions);
            }
        };

        $grant('instructor', ['essay-exams.import', 'essay-exams.submit']);
        $grant('exam-manager', $names);
        $grant('training-office-manager', ['essay-exams.import']);
        $grant('system-manager', $names);
    }

    public function down(): void
    {
        Permission::whereIn('name', ['essay-exams.import', 'essay-exams.submit'])->delete();
    }
};
