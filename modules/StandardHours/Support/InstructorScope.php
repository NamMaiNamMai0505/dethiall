<?php

namespace Modules\StandardHours\Support;

use App\Models\User;
use App\Support\ManagerUnitScope;
use Illuminate\Support\Facades\Auth;
use Modules\Instructor\Models\Instructor;

class InstructorScope
{
    public static function instructorId(): ?int
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isInstructor()) {
            return $user->instructor_id;
        }

        return null;
    }

    public static function apply(array &$filters): void
    {
        $instructorId = self::instructorId();

        if ($instructorId !== null) {
            $filters['instructor_id'] = $instructorId;

            return;
        }

        // Manager: only instructors in managed unit (khoa)
        ManagerUnitScope::applyToFilters($filters);
    }

    public static function ownsRecord(int $recordInstructorId, ?int $researchRecordId = null): bool
    {
        $instructorId = self::instructorId();

        if ($instructorId !== null) {
            if ($recordInstructorId === $instructorId) {
                return true;
            }

            if ($researchRecordId !== null) {
                return \Modules\StandardHours\Models\ResearchRecordMember::query()
                    ->where('research_record_id', $researchRecordId)
                    ->where('instructor_id', $instructorId)
                    ->exists();
            }

            return false;
        }

        // Manager scoped by unit
        if (ManagerUnitScope::isScoped()) {
            return ManagerUnitScope::canAccessInstructor($recordInstructorId);
        }

        return true;
    }

    public static function ensureOwnsRecord(int $recordInstructorId, ?int $researchRecordId = null): void
    {
        if (! self::ownsRecord($recordInstructorId, $researchRecordId)) {
            abort(403, 'Bạn không có quyền truy cập kê khai này.');
        }
    }

    public static function canReview(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ! $user->hasRole('instructor');
    }

    public static function ensureInstructorUser(): int
    {
        $instructorId = self::instructorId();

        if ($instructorId === null) {
            abort(403, 'Tài khoản chưa liên kết với giảng viên.');
        }

        return $instructorId;
    }

    /**
     * Instructors list limited for current actor (manager → khoa).
     */
    public static function instructorsQuery()
    {
        $query = Instructor::active()->with('unit')->orderBy('name');

        if ($id = self::instructorId()) {
            return $query->where('id', $id);
        }

        return ManagerUnitScope::applyToInstructorQuery($query);
    }
}
