<?php

namespace App\Support;

final class ManagementRole
{
    public const SUPER_ADMIN = 'super-admin';

    public const LEGACY_MANAGER = 'manager';

    public const SYSTEM_MANAGER = 'system-manager';

    public const TRAINING_OFFICE_MANAGER = 'training-office-manager';

    /**
     * Quản lý khoa — một vai trò duy nhất cho cấp khoa, phạm vi lọc theo đơn vị
     * gán cho tài khoản. Gộp từ `faculty-schedule-manager` cũ.
     */
    public const FACULTY_MANAGER = 'faculty-manager';

    /**
     * @deprecated Dùng {@see self::FACULTY_MANAGER}. Giữ tên cũ trỏ về vai trò
     * mới để code và migration cũ vẫn nhận đúng cấp khoa.
     */
    public const FACULTY_SCHEDULE_MANAGER = self::FACULTY_MANAGER;

    /** @deprecated Vai trò đã bỏ; nghiệp vụ chuyển cho quản lý toàn trường / khoa. */
    public const STANDARD_HOURS_MANAGER = 'standard-hours-manager';

    /** Vai trò đã loại khỏi hệ thống, migration sẽ chuyển tài khoản rồi xóa. */
    public const RETIRED_ROLES = [
        self::LEGACY_MANAGER,
        'faculty-schedule-manager',
        self::STANDARD_HOURS_MANAGER,
        'approval-agency',
    ];

    /** @return list<string> */
    public static function scopedRoles(): array
    {
        return [
            self::SYSTEM_MANAGER,
            self::TRAINING_OFFICE_MANAGER,
            self::FACULTY_MANAGER,
        ];
    }

    /** @return list<string> */
    public static function managementRoles(bool $includeLegacy = true): array
    {
        $roles = self::scopedRoles();
        if ($includeLegacy) {
            $roles[] = self::LEGACY_MANAGER;
        }

        return $roles;
    }

    /** @return list<string> */
    public static function unitRequiredRoles(): array
    {
        return [
            self::LEGACY_MANAGER,
            self::TRAINING_OFFICE_MANAGER,
            self::FACULTY_MANAGER,
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return RoleCatalog::labels() + [
            self::STANDARD_HOURS_MANAGER => 'Quản lý Giờ chuẩn GV (đã bỏ)',
            self::LEGACY_MANAGER => 'Quản lý (vai trò cũ — chờ chuyển đổi)',
        ];
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? $role;
    }

    /**
     * Ma trận quyền chuẩn của một vai trò — nguồn duy nhất là RoleCatalog.
     *
     * @return list<string>
     */
    public static function permissionNames(string $role): array
    {
        return RoleCatalog::permissionNames($role);
    }

    /** @deprecated Danh sách cứng cũ, giữ để tham chiếu lịch sử migration. */
    private static function legacyPermissionNames(string $role): array
    {
        return match ($role) {
            // Quản lý Phòng Đào tạo là một nhóm vai trò chuẩn — ma trận của nó
            // khai báo trong RoleCatalog để giao diện và audit dùng chung nguồn.
            self::TRAINING_OFFICE_MANAGER => RoleCatalog::permissionNames(self::TRAINING_OFFICE_MANAGER),
            self::FACULTY_SCHEDULE_MANAGER => [
                'dashboards.index',
                'units.index', 'units.show',
                'classes.index', 'classes.show',
                'subjects.index', 'subjects.show',
                'subject-lessons.index', 'subject-lessons.show',
                'subject-lessons.create', 'subject-lessons.edit',
                'instructors.index', 'instructors.show',
                'classrooms.index', 'classrooms.show',
                'training-schedules.index', 'training-schedules.show',
                'schedule-details.index', 'schedule-details.show',
                'schedule-details.edit',
                'teaching-assignments.index', 'teaching-assignments.show',
                'teaching-assignments.create', 'teaching-assignments.edit',
                'export-templates.index', 'export-templates.show',
            ],
            // Quản trị trọn phân hệ Giờ chuẩn: lấy thẳng danh mục ứng dụng để
            // không phải liệt kê tay mỗi lần thêm ứng dụng mới. Giữ kèm quyền
            // gộp cũ (.manage/.settings.*) cho tới khi role cũ chuyển đổi xong.
            self::STANDARD_HOURS_MANAGER => array_values(array_unique(array_merge(
                [
                    'dashboards.index',
                    'units.index', 'units.show',
                    'instructors.index', 'instructors.show',
                    'standard-hours.create', 'standard-hours.edit',
                    'standard-hours.delete', 'standard-hours.approve',
                    'export-templates.index', 'export-templates.show',
                ],
                ApplicationRegistry::subsystemPermissionNames('standard-hours'),
                array_keys(ApplicationRegistry::legacyPermissionMap())
            ))),
            default => [],
        };
    }

    public static function systemManagerMayReceive(string $permission): bool
    {
        if (str_starts_with($permission, 'roles.') || str_starts_with($permission, 'permissions.')) {
            return false;
        }

        return $permission !== 'standard-hours.override-approved';
    }
}
