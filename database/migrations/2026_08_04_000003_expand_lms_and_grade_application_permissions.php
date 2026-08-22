<?php

use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tách LMS và Quản lý điểm thành từng ứng dụng trong ma trận phân quyền.
 *
 * Trước đây cả phân hệ LMS chỉ có `lms.index|create|edit|delete|manage` và
 * Quản lý điểm chỉ có `grades.*` — không phân biệt được ai được sửa bài giảng,
 * ai được chấm thi, ai được duyệt bảng điểm. Migration tạo quyền chi tiết theo
 * ApplicationRegistry và trải quyền tổng cũ sang tương đương để không role nào
 * mất quyền đang dùng.
 */
return new class extends Migration
{
    /** Role tự kê khai — không trải quyền tổng thành quyền quản trị. */
    private const SELF_SERVICE_ROLES = ['student'];

    public function up(): void
    {
        foreach (ApplicationRegistry::permissionNames() as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web']);
        }

        $this->expand('lms', 'lms.');
        $this->expand('grades', 'grades.');
        $this->syncCatalogWideRoles();
    }

    public function down(): void
    {
        // Giữ permission và liên kết role: hạ quyền tự động là thao tác không an toàn.
    }

    /**
     * Quyền tổng của phân hệ → quyền tương ứng trên từng ứng dụng con.
     */
    private function expand(string $subsystem, string $childPrefix): void
    {
        $applications = array_filter(
            ApplicationRegistry::applications(),
            fn (array $a) => $a['subsystem'] === $subsystem
                && str_starts_with($a['permission'], $childPrefix)
        );

        $map = [
            "{$subsystem}.index" => [ApplicationRegistry::ACTION_VIEW],
            "{$subsystem}.show" => [ApplicationRegistry::ACTION_VIEW],
            "{$subsystem}.create" => [ApplicationRegistry::ACTION_CREATE],
            "{$subsystem}.edit" => [ApplicationRegistry::ACTION_EDIT],
            "{$subsystem}.delete" => [ApplicationRegistry::ACTION_DELETE],
            "{$subsystem}.manage" => [ApplicationRegistry::ACTION_APPROVE, ApplicationRegistry::ACTION_EXPORT],
            "{$subsystem}.approve" => [ApplicationRegistry::ACTION_APPROVE],
        ];

        $skip = DB::table('roles')
            ->whereIn('name', self::SELF_SERVICE_ROLES)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        foreach ($map as $coarse => $actions) {
            $roleIds = array_diff($this->roleIdsHolding($coarse), $skip);
            if ($roleIds === []) {
                continue;
            }

            $names = [];
            foreach ($applications as $application) {
                foreach ($actions as $action) {
                    foreach (ApplicationRegistry::permissionNamesFor($application['key'], $action) as $n) {
                        $names[] = $n;
                    }
                }
            }

            foreach ($roleIds as $roleId) {
                $this->grant($roleId, $names);
            }
        }
    }

    private function syncCatalogWideRoles(): void
    {
        $permissions = DB::table('permissions')->where('guard_name', 'web')->pluck('name')->all();

        if ($id = $this->roleId(ManagementRole::SUPER_ADMIN)) {
            $this->grant($id, $permissions);
        }

        if ($id = $this->roleId(ManagementRole::SYSTEM_MANAGER)) {
            $this->grant($id, array_values(array_filter(
                $permissions,
                fn (string $n) => ManagementRole::systemManagerMayReceive($n)
            )));
        }
    }

    private function roleId(string $name): ?int
    {
        $id = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->value('id');

        return $id === null ? null : (int) $id;
    }

    /** @return list<int> */
    private function roleIdsHolding(string $permissionName): array
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', $permissionName)
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id')
            ->unique()
            ->values()
            ->all();
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
};
