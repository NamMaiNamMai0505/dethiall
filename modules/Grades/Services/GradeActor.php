<?php

namespace Modules\Grades\Services;

use App\Models\User;
use App\Support\ApprovalAgency;
use App\Support\TrainingDept;

/**
 * Vai trò nghiệp vụ trong Quản lý điểm / Tổng kết TN.
 * Map từ Spatie + position + unit + config/grades.php
 *
 * admin | board (BGH) | pdot_director (CN PDOT) | pdot_staff (PDOT) | instructor
 */
class GradeActor
{
    public const ADMIN = 'admin';

    public const BOARD = 'board';

    public const PDOT_DIRECTOR = 'pdot_director';

    public const PDOT_STAFF = 'pdot_staff';

    public const INSTRUCTOR = 'instructor';

    public const APPROVAL_AGENCY = 'approval_agency';

    public static function resolve(?User $user = null): string
    {
        $user = $user ?: auth()->user();
        if (! $user) {
            return self::INSTRUCTOR;
        }

        if ($user->isSuperAdmin()) {
            return self::ADMIN;
        }

        if (ApprovalAgency::isStandardHoursGradesAgency($user)) {
            return self::APPROVAL_AGENCY;
        }

        if (ApprovalAgency::isResearchAgency($user)) {
            return self::INSTRUCTOR;
        }

        // Quyền Spatie tường minh (ưu tiên)
        $boardPerm = (string) config('grades.permission_board', 'grades.approve');
        if ($boardPerm !== '' && method_exists($user, 'can') && $user->can($boardPerm)) {
            return self::BOARD;
        }

        $pos = mb_strtolower((string) ($user->position?->name ?? ''));

        if (self::looksLikeBoard($pos)) {
            return self::BOARD;
        }

        $isPdotUnit = TrainingDept::unitIsTrainingOffice($user);

        if ($isPdotUnit) {
            if ($user->can('grades.manage') || self::looksLikePdotDirector($pos)) {
                // Phó trưởng phòng (không phải "phó trưởng phòng đào tạo" rõ) → staff nếu không có grades.manage
                if (
                    str_contains($pos, 'phó trưởng phòng')
                    && ! str_contains($pos, 'đào tạo')
                    && ! str_contains($pos, 'dao tao')
                    && ! $user->can('grades.manage')
                ) {
                    return self::PDOT_STAFF;
                }

                return self::PDOT_DIRECTOR;
            }

            return self::PDOT_STAFF;
        }

        if ($user->isInstructor()) {
            return self::INSTRUCTOR;
        }

        if ($user->isManager()) {
            // Manager khoa: xem/in (staff-like), không sửa điểm toàn cục
            return self::PDOT_STAFF;
        }

        return self::INSTRUCTOR;
    }

    public static function label(?User $user = null): string
    {
        return match (self::resolve($user)) {
            self::ADMIN => 'Admin',
            self::BOARD => 'Ban giám hiệu',
            self::PDOT_DIRECTOR => 'Chủ nhiệm Phòng Đào tạo',
            self::PDOT_STAFF => 'Phòng Đào tạo',
            self::APPROVAL_AGENCY => 'Cơ quan KT&ĐBCLGDĐT',
            default => 'Giảng viên',
        };
    }

    protected static function looksLikeBoard(string $pos): bool
    {
        if ($pos === '') {
            return false;
        }

        foreach ((array) config('grades.board_position_exclude', []) as $ex) {
            if ($ex !== '' && str_contains($pos, mb_strtolower((string) $ex))) {
                return false;
            }
        }

        foreach ((array) config('grades.board_position_needles', []) as $needle) {
            if ($needle !== '' && str_contains($pos, mb_strtolower((string) $needle))) {
                return true;
            }
        }

        return false;
    }

    protected static function looksLikePdotDirector(string $pos): bool
    {
        if ($pos === '') {
            return false;
        }
        foreach ((array) config('grades.pdot_director_position_needles', []) as $needle) {
            if ($needle !== '' && str_contains($pos, mb_strtolower((string) $needle))) {
                return true;
            }
        }

        return false;
    }

    public static function canFull(?User $user = null): bool
    {
        return self::resolve($user) === self::ADMIN;
    }

    public static function canEditOperational(?User $user = null): bool
    {
        $r = self::resolve($user);

        return in_array($r, [self::ADMIN, self::PDOT_DIRECTOR, self::APPROVAL_AGENCY], true);
    }

    public static function canApprove(?User $user = null): bool
    {
        $r = self::resolve($user);

        return in_array($r, [self::ADMIN, self::BOARD, self::APPROVAL_AGENCY], true);
    }

    public static function canView(?User $user = null): bool
    {
        return GradeAccess::canEnter($user ?: auth()->user());
    }

    public static function canPrint(?User $user = null): bool
    {
        return self::canView($user);
    }

    public static function canPrintDiploma(?User $user = null): bool
    {
        $r = self::resolve($user);

        return in_array(
            $r,
            [self::ADMIN, self::BOARD, self::PDOT_DIRECTOR, self::APPROVAL_AGENCY],
            true
        );
    }

    public static function canEnterScores(?User $user = null): bool
    {
        return self::canEditOperational($user);
    }

    public static function isInstructorScoped(?User $user = null): bool
    {
        return self::resolve($user) === self::INSTRUCTOR;
    }
}
