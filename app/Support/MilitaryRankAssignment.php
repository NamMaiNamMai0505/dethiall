<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

final class MilitaryRankAssignment
{
    public const STUDENT_ROLE = 'student';

    /**
     * @return list<int>
     */
    public static function eligibleRoleIds(): array
    {
        return Role::query()
            ->where('name', '!=', self::STUDENT_ROLE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function allows(int $roleId, ?string $userType = null): bool
    {
        if ($userType === 'student') {
            return false;
        }

        return Role::query()
            ->whereKey($roleId)
            ->where('name', '!=', self::STUDENT_ROLE)
            ->exists();
    }
}
