<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['essay-exams.import.index','essay-exams.import.show','essay-exams.import.create','essay-exams.import'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        if ($role = Role::where('name', 'instructor')->where('guard_name', 'web')->first()) {
            $role->givePermissionTo(['essay-exams.import.index','essay-exams.import.show','essay-exams.import.create','essay-exams.import']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Giữ các quyền đã có trước đó để không làm mất quyền import của giáo viên.
    }
};
