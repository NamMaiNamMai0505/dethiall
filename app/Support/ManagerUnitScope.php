<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Instructor\Models\Instructor;
use Modules\Unit\Models\Unit;

/**
 * Scope data access for manager role by assigned unit (khoa).
 * Super-admin is never scoped.
 */
class ManagerUnitScope
{
    /**
     * Manager (not super-admin) with unit restriction.
     */
    public static function isScoped(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        // Cơ quan phê duyệt cần xem hồ sơ toàn trường, không bị giới hạn vào
        // chính Ban KHQS hoặc Ban KT&ĐBCLGDĐT.
        if (ApprovalAgency::isApprovalAgencyUnit($user)) {
            return false;
        }

        return $user->isManager() || $user->isFacultyScheduleManager();
    }

    public static function managedUnitId(?User $user = null): ?int
    {
        $user ??= Auth::user();

        if (! self::isScoped($user)) {
            return null;
        }

        // Role Khoa mới bắt buộc cấu hình functional scope hợp lệ. Manager cũ vẫn
        // giữ phạm vi unit hiện tại cho các module ngoài lịch trong giai đoạn chuyển đổi.
        if (
            $user->isFacultyScheduleManager()
            && TrainingScheduleAccess::scope($user) !== TrainingScheduleAccess::SCOPE_FACULTY
        ) {
            return null;
        }

        return $user->unit_id ? (int) $user->unit_id : null;
    }

    /**
     * Unit + all descendant unit IDs (khoa and sub-units).
     *
     * @return list<int>
     */
    public static function managedUnitIds(?User $user = null): array
    {
        $unitId = self::managedUnitId($user);

        if ($unitId === null) {
            // Manager without unit: no data (use impossible id)
            return self::isScoped($user) ? [-1] : [];
        }

        return self::unitAndDescendantIds($unitId);
    }

    /**
     * @return list<int>
     */
    public static function unitAndDescendantIds(int $unitId): array
    {
        static $cache = [];
        if (array_key_exists($unitId, $cache)) {
            return $cache[$unitId];
        }

        $ids = [$unitId];
        $frontier = [$unitId];

        while ($frontier !== []) {
            $children = Unit::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $new = array_values(array_diff($children, $ids));
            if ($new === []) {
                break;
            }
            $ids = array_merge($ids, $new);
            $frontier = $new;
        }

        return $cache[$unitId] = array_values(array_unique($ids));
    }

    public static function applyToInstructorQuery(Builder $query, ?User $user = null): Builder
    {
        if (! self::isScoped($user)) {
            return $query;
        }

        return $query->whereIn('unit_id', self::managedUnitIds($user));
    }

    /**
     * Inject unit_ids into filter arrays used by services.
     */
    public static function applyToFilters(array &$filters, ?User $user = null): void
    {
        if (! self::isScoped($user)) {
            return;
        }

        $managed = self::managedUnitIds($user);
        $requested = $filters['unit_id'] ?? null;
        $requestedIds = $filters['unit_ids'] ?? null;

        if (is_array($requestedIds) && $requestedIds !== []) {
            $filters['unit_ids'] = array_values(array_intersect(
                array_map('intval', $requestedIds),
                $managed
            )) ?: [-1];
        } elseif ($requested !== null && $requested !== '') {
            $rid = (int) $requested;
            $filters['unit_id'] = in_array($rid, $managed, true) ? $rid : -1;
            $filters['unit_ids'] = [$filters['unit_id']];
        } else {
            $filters['unit_ids'] = $managed;
        }
    }

    public static function canAccessInstructor(int|Instructor $instructor, ?User $user = null): bool
    {
        if (! self::isScoped($user)) {
            return true;
        }

        $instructorId = $instructor instanceof Instructor ? (int) $instructor->id : $instructor;
        $unitId = $instructor instanceof Instructor
            ? (int) ($instructor->unit_id ?? 0)
            : (int) Instructor::query()->whereKey($instructorId)->value('unit_id');

        return $unitId > 0 && in_array($unitId, self::managedUnitIds($user), true);
    }

    public static function ensureCanAccessInstructor(int|Instructor $instructor, ?User $user = null): void
    {
        if (! self::canAccessInstructor($instructor, $user)) {
            abort(403, 'Bạn chỉ được quản lý giảng viên thuộc khoa được phân công.');
        }
    }

    public static function canAccessUnit(int $unitId, ?User $user = null): bool
    {
        if (! self::isScoped($user)) {
            return true;
        }

        return in_array($unitId, self::managedUnitIds($user), true);
    }

    /**
     * Units available in filter dropdowns for current user.
     *
     * @return array<int, string>
     */
    public static function unitOptions(?User $user = null): array
    {
        $query = Unit::query()->orderBy('name');

        if (self::isScoped($user)) {
            $query->whereIn('id', self::managedUnitIds($user));
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Label for manager's unit (for UI).
     */
    public static function managedUnitName(?User $user = null): ?string
    {
        $user ??= Auth::user();
        $id = self::managedUnitId($user);

        if (! $id) {
            return null;
        }

        return Unit::query()->whereKey($id)->value('name');
    }
}
