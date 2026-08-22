<?php

namespace Modules\User\Support;

use App\Support\ApplicationRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Dựng ma trận phân quyền theo đúng "Bảng phân quyền vai trò":
 * Phân hệ → Ứng dụng (hàng) → Xem / Thêm / Sửa / Xóa / Duyệt / Xuất (cột).
 *
 * Một ô có thể gộp nhiều permission thật (ví dụ "Xem" của module lõi = index +
 * show); ApplicationRegistry giữ ánh xạ đó, view chỉ hiển thị ngôn ngữ nghiệp vụ.
 */
class PermissionMatrix
{
    /**
     * @param  list<string>  $grantedPermissionNames
     * @return array{
     *     subsystems: list<array<string, mixed>>,
     *     actions: list<string>,
     *     actionLabels: array<string, string>,
     *     extraPermissions: list<array{id: int, name: string, granted: bool}>,
     *     grantedCount: int
     * }
     */
    public static function build(array $grantedPermissionNames = []): array
    {
        $granted = collect($grantedPermissionNames)->flip();
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);
        $existingNames = $existing->pluck('name')->flip();

        $subsystems = [];
        $covered = [];

        foreach (ApplicationRegistry::subsystems() as $subsystem) {
            $applications = [];

            foreach ($subsystem['applications'] as $application) {
                $cells = [];

                foreach (ApplicationRegistry::actionOrder() as $action) {
                    $names = ApplicationRegistry::permissionNamesFor($application['key'], $action);

                    if ($names === []) {
                        $cells[$action] = null;

                        continue;
                    }

                    // Chỉ tính những permission đã tồn tại trong DB; permission
                    // chưa migrate sẽ không hiển thị như "thiếu quyền".
                    $available = array_values(array_filter(
                        $names,
                        fn (string $name) => $existingNames->has($name)
                    ));

                    if ($available === []) {
                        $cells[$action] = null;

                        continue;
                    }

                    foreach ($available as $name) {
                        $covered[$name] = true;
                    }

                    $grantedHere = array_values(array_filter(
                        $available,
                        fn (string $name) => $granted->has($name)
                    ));

                    $cells[$action] = [
                        'permissions' => $available,
                        'granted' => count($grantedHere) === count($available),
                        'partial' => $grantedHere !== [] && count($grantedHere) !== count($available),
                    ];
                }

                $applications[] = [
                    'key' => $application['key'],
                    'label' => $application['label'],
                    'note' => $application['note'] ?? null,
                    'permission' => $application['permission'],
                    'cells' => $cells,
                ];
            }

            $subsystems[] = [
                'key' => $subsystem['key'],
                'label' => $subsystem['label'],
                'applications' => $applications,
            ];
        }

        $extraPermissions = $existing
            ->reject(fn (Permission $permission) => isset($covered[$permission->name]))
            ->map(fn (Permission $permission) => [
                'id' => (int) $permission->id,
                'name' => $permission->name,
                'granted' => $granted->has($permission->name),
            ])
            ->values()
            ->all();

        return [
            'subsystems' => $subsystems,
            'actions' => ApplicationRegistry::actionOrder(),
            'actionLabels' => ApplicationRegistry::actionLabels(),
            'extraPermissions' => $extraPermissions,
            'grantedCount' => $granted->count(),
        ];
    }

    /**
     * Mức phủ của từng vai trò trên từng phân hệ — dùng cho danh sách vai trò
     * để nhìn phát biết vai trò nào chạm được phân hệ nào, không phải mở từng cái.
     *
     * @param  Collection<int, Role>  $roles
     * @return array{
     *     labels: list<array{key: string, label: string, total: int}>,
     *     byRole: array<int, array<string, array{used: int, total: int, write: bool}>>
     * }
     */
    public static function subsystemCoverage(Collection $roles): array
    {
        $labels = [];
        $applicationsBySubsystem = [];

        foreach (ApplicationRegistry::subsystems() as $subsystem) {
            $labels[] = [
                'key' => $subsystem['key'],
                'label' => $subsystem['label'],
                'total' => count($subsystem['applications']),
            ];
            $applicationsBySubsystem[$subsystem['key']] = $subsystem['applications'];
        }

        $byRole = [];

        foreach ($roles as $role) {
            $granted = $role->permissions->pluck('name')->flip();
            $row = [];

            foreach ($applicationsBySubsystem as $key => $applications) {
                $used = 0;
                $write = false;

                foreach ($applications as $application) {
                    $canView = false;
                    foreach (ApplicationRegistry::permissionNamesFor($application['key'], ApplicationRegistry::ACTION_VIEW) as $name) {
                        if ($granted->has($name)) {
                            $canView = true;
                            break;
                        }
                    }

                    if ($canView) {
                        $used++;
                    }

                    if ($write) {
                        continue;
                    }

                    foreach ([
                        ApplicationRegistry::ACTION_CREATE,
                        ApplicationRegistry::ACTION_EDIT,
                        ApplicationRegistry::ACTION_DELETE,
                    ] as $action) {
                        foreach (ApplicationRegistry::permissionNamesFor($application['key'], $action) as $name) {
                            if ($granted->has($name)) {
                                $write = true;
                                break 2;
                            }
                        }
                    }
                }

                $row[$key] = ['used' => $used, 'total' => count($applications), 'write' => $write];
            }

            $byRole[(int) $role->id] = $row;
        }

        return ['labels' => $labels, 'byRole' => $byRole];
    }

    /**
     * Chuyển lựa chọn trên ma trận (ứng dụng → hành động) thành tên permission.
     *
     * @param  array<string, list<string>>  $abilities
     * @return list<string>
     */
    public static function resolvePermissionNames(array $abilities): array
    {
        $applications = ApplicationRegistry::applications();
        $names = [];

        foreach ($abilities as $applicationKey => $actions) {
            if (! isset($applications[$applicationKey]) || ! is_array($actions)) {
                continue;
            }

            foreach ($actions as $action) {
                foreach (ApplicationRegistry::permissionNamesFor($applicationKey, (string) $action) as $name) {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Permission nằm ngoài danh mục ứng dụng (quyền gộp cũ, quyền kỹ thuật).
     *
     * @return Collection<int, Permission>
     */
    public static function outOfCatalogPermissions(): Collection
    {
        $covered = collect(ApplicationRegistry::permissionNames())->flip();

        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->reject(fn (Permission $permission) => $covered->has($permission->name))
            ->values();
    }

    public static function actionLabel(string $action): string
    {
        return ApplicationRegistry::actionLabel($action);
    }

    public static function applicationLabel(string $applicationKey): string
    {
        return ApplicationRegistry::label($applicationKey);
    }
}
