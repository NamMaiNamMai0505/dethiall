<?php

use App\Support\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $legacyNames = [
            'Quân nhân : đề xuất phép' => RoleCatalog::LEAVE_MILITARY,
            'chỉ huy cơ quan : duyệt phép và đưa phép lên cho các cơ quan quản lý xem xét' => RoleCatalog::LEAVE_COMMANDER,
            'Quân lực: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân' => RoleCatalog::LEAVE_QUAN_LUC,
            'Cơ quan cán bộ: in giấy cho ban giám hiệu kí và duyệt sau khi duyệt thì đồng thời gửi thông báo về cho quân nhân' => RoleCatalog::LEAVE_MANAGEMENT_AGENCY,
        ];
        foreach ($legacyNames as $oldName => $newName) {
            $old = Role::where('guard_name', 'web')->where('name', $oldName)->first();
            if (!$old || $old->name === $newName) continue;
            $new = Role::firstOrCreate(['name' => $newName, 'guard_name' => 'web']);
            $new->syncPermissions($old->permissions);
            DB::table('model_has_roles')->where('role_id', $old->id)->update(['role_id' => $new->id]);
            $old->delete();
        }

        foreach (RoleCatalog::groups() as $group) {
            $role = Role::firstOrCreate([
                'name' => $group['name'],
                'guard_name' => 'web',
            ]);

            $permissions = collect(RoleCatalog::permissionNames($group['name']))
                ->map(fn (string $name) => Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]))
                ->all();

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Không xóa vai trò chuẩn khi rollback để tránh làm mất liên kết tài khoản.
    }
};
