<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $mine = Permission::firstOrCreate(['name' => 'essay-exams.mine', 'guard_name' => 'web']);
        $show = Permission::firstOrCreate(['name' => 'essay-exams.show', 'guard_name' => 'web']);

        $instructor = Role::query()->where('name', 'instructor')->where('guard_name', 'web')->first();
        if ($instructor) {
            $instructor->givePermissionTo([$mine, $show]);
            $instructor->revokePermissionTo('essay-exams.index');
        }
    }

    public function down(): void
    {
        // Preserve administrator-customized grants after deployment.
    }
};
