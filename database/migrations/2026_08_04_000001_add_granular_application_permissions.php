<?php

use App\Support\ApplicationRegistry;
use App\Support\ManagementRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tạo permission chi tiết theo ApplicationRegistry và chuyển quyền gộp cũ sang
 * quyền chi tiết tương đương.
 *
 * Trước đây một role chỉ cần `standard-hours.index` là xem được toàn bộ 15 ứng
 * dụng Giờ chuẩn, và `standard-hours.create|edit` là sửa được tất cả. Migration
 * này "trải" các quyền tổng đó thành quyền từng ứng dụng, để bước sau gỡ fallback
 * trong StandardHoursBaseController mà không role nào mất quyền đang dùng.
 */
return new class extends Migration
{
    /** Role tự kê khai — không được trải quyền tổng thành quyền quản trị danh mục. */
    private const SELF_SERVICE_ROLES = ['instructor', 'student'];

    public function up(): void
    {
        foreach (ApplicationRegistry::permissionNames() as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $this->expandLegacyManagePermissions();
        $this->expandCoarseStandardHoursPermissions();
        $this->syncCatalogWideRoles();
    }

    public function down(): void
    {
        // Giữ lại permission và các liên kết role: hạ cấp quyền tự động là thao
        // tác không an toàn, quản trị viên chủ động thu hồi trong màn hình Vai trò.
    }

    /**
     * `<app>.manage` → `<app>.create` + `.edit` + `.delete`
     * `standard-hours.settings.*` → hai ứng dụng Kỳ tính năm học / Luật quy đổi.
     */
    private function expandLegacyManagePermissions(): void
    {
        foreach (ApplicationRegistry::legacyPermissionMap() as $legacyName => $granularNames) {
            foreach ($this->roleIdsHolding([$legacyName]) as $roleId) {
                $this->grant($roleId, $granularNames);
            }
        }
    }

    /**
     * Quyền tổng của phân hệ Giờ chuẩn → quyền tương ứng trên từng ứng dụng.
     */
    private function expandCoarseStandardHoursPermissions(): void
    {
        $applications = array_filter(
            ApplicationRegistry::applications(),
            fn (array $application) => $application['subsystem'] === 'standard-hours'
                && $application['permission'] !== 'standard-hours'
        );

        $coarseMap = [
            'standard-hours.index' => [ApplicationRegistry::ACTION_VIEW],
            'standard-hours.create' => [ApplicationRegistry::ACTION_CREATE],
            'standard-hours.edit' => [ApplicationRegistry::ACTION_EDIT],
            'standard-hours.delete' => [ApplicationRegistry::ACTION_DELETE],
            'standard-hours.approve' => [ApplicationRegistry::ACTION_APPROVE],
        ];

        $selfServiceRoleIds = DB::table('roles')
            ->whereIn('name', self::SELF_SERVICE_ROLES)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        foreach ($coarseMap as $coarseName => $actions) {
            $roleIds = array_diff($this->roleIdsHolding([$coarseName]), $selfServiceRoleIds);
            if ($roleIds === []) {
                continue;
            }

            $granularNames = [];
            foreach ($applications as $application) {
                foreach ($actions as $action) {
                    foreach (ApplicationRegistry::permissionNamesFor($application['key'], $action) as $name) {
                        $granularNames[] = $name;
                    }
                }
            }

            foreach ($roleIds as $roleId) {
                $this->grant($roleId, $granularNames);
            }
        }
    }

    /**
     * `super-admin` luôn giữ toàn bộ quyền, `system-manager` giữ toàn bộ quyền
     * nghiệp vụ. Hai role này được chốt theo danh mục permission tại thời điểm
     * migration trước chạy, nên phải bù các permission mới sinh ra ở đây.
     */
    private function syncCatalogWideRoles(): void
    {
        $permissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $superAdminId = $this->roleId(ManagementRole::SUPER_ADMIN);
        if ($superAdminId !== null) {
            $this->grant($superAdminId, $permissions);
        }

        $systemManagerId = $this->roleId(ManagementRole::SYSTEM_MANAGER);
        if ($systemManagerId !== null) {
            $this->grant($systemManagerId, array_values(array_filter(
                $permissions,
                fn (string $name) => ManagementRole::systemManagerMayReceive($name)
            )));
        }
    }

    private function roleId(string $name): ?int
    {
        $id = DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<int>
     */
    private function roleIdsHolding(array $permissionNames): array
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('permissions.name', $permissionNames)
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id')
            ->unique()
            ->values()
            ->all();
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
