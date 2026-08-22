<?php

use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gom về đúng 8 vai trò chuẩn của nhà trường.
 *
 * Trước đây tồn tại song song `manager` (vai trò cũ), `faculty-schedule-manager`,
 * `standard-hours-manager` và `approval-agency` — chồng lấn nhau nên khó biết ai
 * làm được gì. Migration chuyển tài khoản sang vai trò tương đương rồi xóa vai
 * trò thừa; vai trò do quản trị viên tự tạo về sau không bị ảnh hưởng.
 */
return new class extends Migration
{
    /** Vai trò cũ → vai trò chuẩn tiếp nhận. */
    private const MIGRATE_TO = [
        'manager' => RoleCatalog::FACULTY_MANAGER,
        'faculty-schedule-manager' => RoleCatalog::FACULTY_MANAGER,
        'standard-hours-manager' => ManagementRole::SYSTEM_MANAGER,
        'approval-agency' => RoleCatalog::RESEARCH_AGENCY_MANAGER,
    ];

    public function up(): void
    {
        foreach (RoleCatalog::groups() as $group) {
            $this->ensureRole($group['name']);
        }

        foreach (self::MIGRATE_TO as $old => $new) {
            $oldId = $this->roleId($old);
            $newId = $this->roleId($new);
            if ($oldId === null || $newId === null) {
                continue;
            }

            // Chuyển tài khoản sang vai trò mới, bỏ qua ai đã có sẵn vai trò đó.
            $holders = DB::table('model_has_roles')->where('role_id', $oldId)->get();
            foreach ($holders as $holder) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $newId,
                    'model_type' => $holder->model_type,
                    'model_id' => $holder->model_id,
                ]);
            }
            DB::table('model_has_roles')->where('role_id', $oldId)->delete();

            // users.role_id là liên kết phụ, giữ đồng bộ để màn hình cũ không lệch.
            DB::table('users')->where('role_id', $oldId)->update(['role_id' => $newId]);

            DB::table('role_has_permissions')->where('role_id', $oldId)->delete();
            DB::table('roles')->where('id', $oldId)->delete();
        }

        // Cấp ma trận mặc định cho 8 vai trò chuẩn. Chỉ cấp thêm, không thu hồi
        // — quản trị viên đã chỉnh tay thì phần chỉnh vẫn giữ nguyên.
        foreach (RoleCatalog::groups() as $group) {
            $roleId = $this->roleId($group['name']);
            if ($roleId === null) {
                continue;
            }

            $names = $group['name'] === ManagementRole::SUPER_ADMIN
                ? DB::table('permissions')->where('guard_name', 'web')->pluck('name')->all()
                : RoleCatalog::permissionNames($group['name']);

            $this->grant($roleId, $names);
        }
    }

    /** @param  list<string>  $permissionNames */
    private function grant(int $roleId, array $permissionNames): void
    {
        if ($permissionNames === []) {
            return;
        }

        $ids = DB::table('permissions')
            ->whereIn('name', array_unique($permissionNames))
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($ids as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        // Không dựng lại vai trò đã gộp: tài khoản đã chuyển sang vai trò chuẩn,
        // khôi phục tự động sẽ tạo ra hai vai trò cùng nghĩa.
    }

    private function ensureRole(string $name): int
    {
        $id = $this->roleId($name);
        if ($id !== null) {
            return $id;
        }

        return (int) DB::table('roles')->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function roleId(string $name): ?int
    {
        $id = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->value('id');

        return $id === null ? null : (int) $id;
    }
};
