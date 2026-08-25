<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Safe permission checks — never throw when permission name is missing in DB.
 */
class PermissionCheck
{
    public const LEAVE_AGENCY_ROLE = RoleCatalog::LEAVE_QUAN_LUC;

    public static function isLeaveAgency(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        try {
            return $user->isSuperAdmin() || $user->hasRole(self::LEAVE_AGENCY_ROLE) || $user->hasRole(RoleCatalog::LEAVE_MANAGEMENT_AGENCY);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }

            // Tài khoản quân nhân luôn được vào màn hình gửi đề xuất phép,
            // kể cả khi migration đồng bộ permission chưa chạy hoặc cache quyền cũ.
            if (method_exists($user, 'hasRole')
                && $user->hasRole(RoleCatalog::LEAVE_MILITARY)
                && in_array($permission, [
                    'leave-management.access.index',
                    'leave-management.access.show',
                    'leave-management.index',
                    'leave-management.create',
                    'leave-management.requests.index',
                    'leave-management.requests.show',
                    'leave-management.requests.create',
                ], true)) {
                return true;
            }

            // Quyền dashboard được cấp riêng cho tài khoản Nguyễn Văn D theo
            // yêu cầu nghiệp vụ, kể cả khi migration quyền chưa được chạy.
            if (trim((string) $user->name) === 'Nguyễn Văn D' && in_array($permission, [
                'dashboards.index',
                'leave-management.access.index',
                'leave-management.access.show',
                'leave-management.index',
                'leave-management.approvals.index',
                'leave-management.approvals.approve',
                'leave-management.approve',
            ], true)) {
                return true;
            }

            $approvalDecision = ApprovalAgency::permissionDecision($user, $permission);
            if ($approvalDecision !== null) {
                return $approvalDecision;
            }

            return $user->can($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function userCan(string $permission): bool
    {
        $user = Auth::user();

        return $user instanceof User && self::can($user, $permission);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function userCanAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::userCan($permission)) {
                return true;
            }
        }

        return false;
    }
}
