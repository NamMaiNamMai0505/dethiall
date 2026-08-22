<?php

namespace App\Support;

final class RoleDisplay
{
    public const EXAM_MANAGER = 'exam-manager';

    public const APPROVAL_AGENCY = ApprovalAgency::ROLE;

    public static function label(string $role): string
    {
        // Nhóm vai trò chuẩn khai báo trong RoleCatalog được ưu tiên.
        $catalog = RoleCatalog::labels();
        if (isset($catalog[$role])) {
            return $catalog[$role];
        }

        return match ($role) {
            ManagementRole::SUPER_ADMIN => 'Quản trị hệ thống',
            ManagementRole::LEGACY_MANAGER => 'Quản lý (vai trò cũ)',
            ManagementRole::SYSTEM_MANAGER => 'Quản lý toàn hệ thống',
            ManagementRole::TRAINING_OFFICE_MANAGER => 'Quản lý lịch đào tạo — Phòng Đào tạo',
            ManagementRole::FACULTY_SCHEDULE_MANAGER => 'Quản lý lịch đào tạo — Khoa',
            ManagementRole::STANDARD_HOURS_MANAGER => 'Quản lý Giờ chuẩn GV',
            'instructor' => 'Giảng viên',
            'student' => 'Học viên',
            self::EXAM_MANAGER => 'Khảo thí – Giờ chuẩn GV & Điểm thi',
            self::APPROVAL_AGENCY => 'Cơ Quan Phê Duyệt',
            default => $role,
        };
    }
}
