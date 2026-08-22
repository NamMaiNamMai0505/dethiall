<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

class RoleAssignment
{
    public const MANAGER_ROLE = ManagementRole::LEGACY_MANAGER;

    public const SUPER_ADMIN_ROLE = ManagementRole::SUPER_ADMIN;

    /**
     * @return Collection<int, Role>
     */
    public static function assignableRoles(?User $actor = null): Collection
    {
        $actor ??= auth()->user();
        $query = Role::query()->orderBy('name');

        if ($actor && ! $actor->hasRole(self::SUPER_ADMIN_ROLE)) {
            $query->where('name', '!=', self::SUPER_ADMIN_ROLE);
        }

        return $query->get();
    }

    public static function assignableRoleOptions(?User $actor = null): array
    {
        return self::assignableRoles($actor)
            ->mapWithKeys(fn (Role $role) => [$role->id => self::label($role->name)])
            ->toArray();
    }

    public static function label(string $roleName): string
    {
        return RoleDisplay::label($roleName);
    }

    public static function ensureCanManageUser(User $target, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        if ($target->hasRole(self::SUPER_ADMIN_ROLE) && ! $actor?->hasRole(self::SUPER_ADMIN_ROLE)) {
            abort(403, 'Bạn không có quyền thao tác với tài khoản Super Admin.');
        }
    }

    public static function ensureAssignableRoleId(int $roleId, ?User $actor = null): void
    {
        $actor ??= auth()->user();
        $role = Role::findOrFail($roleId);

        if ($role->name === self::SUPER_ADMIN_ROLE && ! $actor?->hasRole(self::SUPER_ADMIN_ROLE)) {
            abort(403, 'Bạn không có quyền gán vai trò Super Admin.');
        }
    }

    public static function resolveManagerRoleId(): ?int
    {
        return Role::where('name', self::MANAGER_ROLE)->value('id');
    }

    /** @return list<int> */
    public static function unitRequiredRoleIds(): array
    {
        return Role::query()
            ->whereIn('name', ManagementRole::unitRequiredRoles())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Kiểm tra cặp role + đơn vị trước khi lưu tài khoản quản lý.
     *
     * @return array{field: string, message: string}|null
     */
    public static function roleUnitValidationError(?int $roleId, ?int $unitId, ?string $userType): ?array
    {
        if (! $roleId) {
            return null;
        }

        $roleName = Role::query()->whereKey($roleId)->value('name');
        if (! $roleName || ! in_array($roleName, ManagementRole::managementRoles(), true)) {
            return null;
        }

        if ($userType !== 'internal_user') {
            return [
                'field' => 'user_type',
                'message' => 'Vai trò quản lý chuyên trách chỉ áp dụng cho loại người dùng Nội bộ.',
            ];
        }

        if (in_array($roleName, ManagementRole::unitRequiredRoles(), true) && ! $unitId) {
            return [
                'field' => 'unit_id',
                'message' => 'Vui lòng chọn đơn vị phù hợp với phạm vi của vai trò quản lý.',
            ];
        }

        if (! $unitId || $roleName === ManagementRole::LEGACY_MANAGER) {
            return null;
        }

        $unit = Unit::query()->find($unitId);
        if (! $unit) {
            return null;
        }

        if (
            $roleName === ManagementRole::TRAINING_OFFICE_MANAGER
            && TrainingScheduleAccess::unitFunctionalType($unit) !== Unit::FUNCTIONAL_TRAINING_OFFICE
        ) {
            return [
                'field' => 'unit_id',
                'message' => 'Vai trò Phòng Đào tạo chỉ được gán cho đơn vị có chức năng “Phòng Đào tạo”.',
            ];
        }

        if ($roleName === ManagementRole::FACULTY_SCHEDULE_MANAGER) {
            if (TrainingScheduleAccess::unitFunctionalType($unit) !== Unit::FUNCTIONAL_FACULTY) {
                return [
                    'field' => 'unit_id',
                    'message' => 'Vai trò quản lý lịch của Khoa chỉ được gán cho đơn vị có chức năng “Khoa”.',
                ];
            }

            if (TrainingScheduleAccess::facultyCodeForUnit($unit) === null) {
                return [
                    'field' => 'unit_id',
                    'message' => 'Đơn vị Khoa phải có mã phạm vi hợp lệ từ K1 đến K8 trước khi gán vai trò này.',
                ];
            }
        }

        return null;
    }

    /**
     * @param  Builder<User>  $query
     */
    public static function applyVisibleUsersScope($query, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        if ($actor && ! $actor->hasRole(self::SUPER_ADMIN_ROLE)) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', self::SUPER_ADMIN_ROLE);
            });
        }
    }
}
