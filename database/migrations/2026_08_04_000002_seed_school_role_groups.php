<?php

use App\Support\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tạo các nhóm vai trò theo chức trách của nhà trường (Quản lý khoa, Quản lý
 * Phòng Đào tạo, Ban Khảo thí, Ban Khoa học Quân sự, Giảng viên, Học viên) và
 * cấp ma trận quyền mặc định khai báo trong RoleCatalog.
 *
 * Chỉ cấp thêm, không thu hồi: vai trò đã tồn tại và đã được quản trị viên chỉnh
 * tay sẽ giữ nguyên phần đã chỉnh, tránh migration ghi đè cấu hình thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (RoleCatalog::groups() as $group) {
            $roleId = $this->ensureRole($group['name']);
            $this->grant($roleId, RoleCatalog::permissionNames($group['name']));
        }
    }

    public function down(): void
    {
        // Chỉ xóa vai trò mới sinh và chưa gán cho tài khoản nào.
        foreach ([RoleCatalog::FACULTY_MANAGER, RoleCatalog::RESEARCH_AGENCY_MANAGER] as $name) {
            $roleId = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->value('id');
            if ($roleId === null) {
                continue;
            }

            $inUse = DB::table('model_has_roles')->where('role_id', $roleId)->exists();
            if ($inUse) {
                continue;
            }

            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }

    private function ensureRole(string $name): int
    {
        $roleId = DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId !== null) {
            return (int) $roleId;
        }

        return (int) DB::table('roles')->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $permissionNames
     */
    private function grant(int $roleId, array $permissionNames): void
    {
        if ($permissionNames === []) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_unique($permissionNames))
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
