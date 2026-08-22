<?php

namespace Modules\Dashboard\Support;

use App\Models\User;
use App\Support\ManagerUnitScope;
use Illuminate\Database\Eloquent\Builder;

final class DashboardScope
{
    public const TYPE_GLOBAL = 'global';

    public const TYPE_UNIT = 'unit';

    public const TYPE_INSTRUCTOR = 'instructor';

    public const TYPE_ACCOUNT = 'account';

    /**
     * @return array{
     *   type:string,
     *   is_global:bool,
     *   instructor_id:int|null,
     *   unit_id:int|null,
     *   unit_ids:list<int>,
     *   label:string,
     *   short_label:string
     * }
     */
    public static function resolve(User $user): array
    {
        $user->loadMissing(['instructor.unit', 'unit']);

        if (
            $user->isSuperAdmin()
            || $user->isSystemManager()
            || $user->isTrainingOfficeManager()
            || $user->isStandardHoursManager()
        ) {
            return [
                'type' => self::TYPE_GLOBAL,
                'is_global' => true,
                'instructor_id' => null,
                'unit_id' => null,
                'unit_ids' => [],
                'label' => 'Toàn hệ thống',
                'short_label' => 'Toàn hệ thống',
            ];
        }

        if ($user->isManager() || $user->isFacultyScheduleManager()) {
            $unitIds = ManagerUnitScope::managedUnitIds($user);
            $unitId = $user->unit_id ? (int) $user->unit_id : null;
            $unitName = $user->unit?->name ?: 'Đơn vị được phân công';

            return [
                'type' => self::TYPE_UNIT,
                'is_global' => false,
                'instructor_id' => null,
                'unit_id' => $unitId,
                'unit_ids' => $unitIds,
                'label' => 'Đơn vị: '.$unitName,
                'short_label' => $unitName,
            ];
        }

        if ($user->instructor_id) {
            $instructorName = $user->instructor?->name ?: $user->name;

            return [
                'type' => self::TYPE_INSTRUCTOR,
                'is_global' => false,
                'instructor_id' => (int) $user->instructor_id,
                'unit_id' => $user->instructor?->unit_id ? (int) $user->instructor->unit_id : null,
                'unit_ids' => $user->instructor?->unit_id ? [(int) $user->instructor->unit_id] : [],
                'label' => 'Cá nhân: '.$instructorName,
                'short_label' => $instructorName,
            ];
        }

        if ($user->unit_id) {
            $unitName = $user->unit?->name ?: 'Đơn vị của bạn';

            return [
                'type' => self::TYPE_UNIT,
                'is_global' => false,
                'instructor_id' => null,
                'unit_id' => (int) $user->unit_id,
                'unit_ids' => [(int) $user->unit_id],
                'label' => 'Đơn vị: '.$unitName,
                'short_label' => $unitName,
            ];
        }

        return [
            'type' => self::TYPE_ACCOUNT,
            'is_global' => false,
            'instructor_id' => null,
            'unit_id' => null,
            'unit_ids' => [-1],
            'label' => 'Tài khoản của bạn',
            'short_label' => $user->name,
        ];
    }

    public static function applyToScheduleQuery(Builder $query, array $scope): Builder
    {
        if ($scope['is_global'] ?? false) {
            return $query;
        }

        if (! empty($scope['instructor_id'])) {
            return $query->where('instructor_id', (int) $scope['instructor_id']);
        }

        $unitIds = array_values(array_filter(
            array_map('intval', $scope['unit_ids'] ?? []),
            fn (int $id) => $id > 0
        ));

        if ($unitIds !== []) {
            return $query->whereHas('instructor', fn (Builder $instructorQuery) => $instructorQuery
                ->whereIn('unit_id', $unitIds));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function applyToInstructorQuery(Builder $query, array $scope): Builder
    {
        if ($scope['is_global'] ?? false) {
            return $query;
        }

        if (! empty($scope['instructor_id'])) {
            return $query->whereKey((int) $scope['instructor_id']);
        }

        $unitIds = array_values(array_filter(
            array_map('intval', $scope['unit_ids'] ?? []),
            fn (int $id) => $id > 0
        ));

        return $unitIds !== []
            ? $query->whereIn('unit_id', $unitIds)
            : $query->whereRaw('1 = 0');
    }
}
