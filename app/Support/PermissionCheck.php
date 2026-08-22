<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Safe permission checks — never throw when permission name is missing in DB.
 */
class PermissionCheck
{
    public static function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
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
