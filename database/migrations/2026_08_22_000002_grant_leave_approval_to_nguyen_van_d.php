<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'leave-management.access.index',
            'leave-management.index',
            'leave-management.approvals.index',
            'leave-management.approvals.approve',
            'leave-management.approve',
        ];

        $users = User::query()->where('name', 'Nguyễn Văn D')->get();
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $users->each(fn (User $user) => $user->givePermissionTo($permission));
        }
    }

    public function down(): void
    {
        $users = User::query()->where('name', 'Nguyễn Văn D')->get();
        foreach ([
            'leave-management.access.index',
            'leave-management.index',
            'leave-management.approvals.index',
            'leave-management.approvals.approve',
            'leave-management.approve',
        ] as $name) {
            $permission = Permission::query()->where('name', $name)->where('guard_name', 'web')->first();
            if ($permission) $users->each(fn (User $user) => $user->revokePermissionTo($permission));
        }
    }
};
