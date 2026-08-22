<?php

namespace App\Support;

use Modules\StandardHours\Models\Position;

class ManagerPosition
{
    public const NAMES = [
        'Hiệu trưởng',
        'Phó Hiệu trưởng',
        'Trưởng phòng',
        'Chủ nhiệm khoa',
        'Phó Chủ nhiệm khoa',
        'Trưởng bộ môn',
    ];

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return Position::query()
            ->whereIn('name', self::NAMES)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function isManagerPosition(?int $positionId): bool
    {
        if ($positionId === null) {
            return false;
        }

        return Position::query()
            ->whereKey($positionId)
            ->whereIn('name', self::NAMES)
            ->exists();
    }
}
