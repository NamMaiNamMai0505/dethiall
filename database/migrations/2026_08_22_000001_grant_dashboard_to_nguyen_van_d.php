<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'dashboards.index',
            'guard_name' => 'web',
        ]);

        User::query()
            ->where('name', 'Nguyễn Văn D')
            ->get()
            ->each(fn (User $user) => $user->givePermissionTo($permission));
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'dashboards.index')
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        User::query()
            ->where('name', 'Nguyễn Văn D')
            ->get()
            ->each(fn (User $user) => $user->revokePermissionTo($permission));
    }
};
