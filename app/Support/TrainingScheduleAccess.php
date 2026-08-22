<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Instructor\Models\Instructor;
use Modules\Subject\Models\Subject;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

final class TrainingScheduleAccess
{
    public const SCOPE_SYSTEM = 'system';

    public const SCOPE_TRAINING_OFFICE = 'training_office';

    public const SCOPE_FACULTY = 'faculty';

    public const SCOPE_NONE = 'none';

    public static function user(?User $user = null): ?User
    {
        $user ??= Auth::user();
        if ($user && ! $user->relationLoaded('unit')) {
            $user->loadMissing('unit');
        }

        return $user;
    }

    public static function scope(?User $user = null): string
    {
        $user = self::user($user);
        if (! $user) {
            return self::SCOPE_NONE;
        }

        $facultyRoleId = Role::query()->where('name', ManagementRole::FACULTY_SCHEDULE_MANAGER)->value('id');
        $isFacultyScheduleManager = $user->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER)
            || ($facultyRoleId && (int) $user->role_id === (int) $facultyRoleId);

        if ($user->isSuperAdmin() || $user->hasRole(ManagementRole::SYSTEM_MANAGER)) {
            return self::SCOPE_SYSTEM;
        }

        if ($user->hasRole(ManagementRole::TRAINING_OFFICE_MANAGER)) {
            return self::unitIsTrainingOffice($user) ? self::SCOPE_TRAINING_OFFICE : self::SCOPE_NONE;
        }

        if ($isFacultyScheduleManager) {
            return self::unitIsFaculty($user) ? self::SCOPE_FACULTY : self::SCOPE_NONE;
        }

        // Tương thích tạm cho manager cũ, nhưng không còn coi mọi unit là khoa.
        if ($user->hasRole(ManagementRole::LEGACY_MANAGER)) {
            if (self::unitIsTrainingOffice($user)) {
                return self::SCOPE_TRAINING_OFFICE;
            }
            if (self::unitIsFaculty($user)) {
                return self::SCOPE_FACULTY;
            }
        }

        return self::SCOPE_NONE;
    }

    public static function canManageSkeleton(?User $user = null): bool
    {
        return in_array(self::scope($user), [self::SCOPE_SYSTEM, self::SCOPE_TRAINING_OFFICE], true);
    }

    public static function canAssignFacultySchedule(?User $user = null): bool
    {
        return in_array(self::scope($user), [self::SCOPE_SYSTEM, self::SCOPE_FACULTY], true);
    }

    public static function canManageScheduleDetails(?User $user = null): bool
    {
        return self::canManageSkeleton($user) || self::canAssignFacultySchedule($user);
    }

    public static function ensureValidRoleConfiguration(?User $user = null): void
    {
        $user = self::user($user);
        if (! $user) {
            abort(401);
        }

        $facultyRoleId = Role::query()->where('name', ManagementRole::FACULTY_SCHEDULE_MANAGER)->value('id');
        $hasFacultyScheduleRole = $user->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER)
            || ($facultyRoleId && (int) $user->role_id === (int) $facultyRoleId);
        $hasScheduleManagementRole = $user->hasAnyRole([
            ManagementRole::SYSTEM_MANAGER,
            ManagementRole::TRAINING_OFFICE_MANAGER,
            ManagementRole::FACULTY_SCHEDULE_MANAGER,
            ManagementRole::LEGACY_MANAGER,
        ]) || $hasFacultyScheduleRole;

        abort_if(
            $hasScheduleManagementRole && self::scope($user) === self::SCOPE_NONE,
            403,
            'Vai trò quản lý lịch chưa được gắn đúng loại đơn vị. Vui lòng liên hệ quản trị hệ thống.'
        );

        // Dedicated faculty schedule roles must have an explicitly configured
        // faculty unit; a legacy unit code alone must not grant global access.
        if ($hasFacultyScheduleRole) {
            $configuredUnit = Unit::query()->find($user->unit_id);
            abort_unless(
                $configuredUnit
                    && self::unitFunctionalType($configuredUnit) === Unit::FUNCTIONAL_FACULTY
                    && self::facultyCodeForUnit($configuredUnit) !== null,
                403,
                'Vai trò quản lý lịch chưa được gắn đúng loại đơn vị. Vui lòng liên hệ quản trị hệ thống.'
            );
        }
    }

    public static function ensureCanManageSkeleton(?User $user = null): void
    {
        abort_unless(
            self::canManageSkeleton($user),
            403,
            'Chỉ Phòng Đào tạo hoặc quản trị hệ thống được thay đổi khung lịch đào tạo.'
        );
    }

    public static function ensureCanManageScheduleDetails(?User $user = null): void
    {
        abort_unless(
            self::canManageScheduleDetails($user),
            403,
            'Tài khoản không có phạm vi chỉnh sửa lịch học chi tiết.'
        );
    }

    /**
     * Buổi học đã qua ngày + hết ân hạn thì khoá sửa. Khoá theo NGÀY (không
     * theo từng tiết) — chưa có bảng giờ tiết nên không biết tiết cuối trong
     * ngày kết thúc lúc mấy giờ.
     */
    public static function isScheduleDetailDateLocked(string $date): bool
    {
        $graceHours = (int) config('training_schedule.edit_grace_hours', 48);
        $deadline = Carbon::parse($date)->endOfDay()->addHours($graceHours);

        return now()->gt($deadline);
    }

    /**
     * Chặn sửa buổi đã học xong và hết ân hạn. Super Admin là ngoại lệ duy
     * nhất, và mỗi lần dùng ngoại lệ đều được ghi log để truy vết.
     */
    public static function ensureScheduleDetailDateEditable(string $date, ?User $user = null): void
    {
        $user = self::user($user);

        if (! self::isScheduleDetailDateLocked($date)) {
            return;
        }

        if ($user?->isSuperAdmin()) {
            Log::warning('Super Admin sửa lịch học đã khoá theo thời gian.', [
                'user_id' => $user->id,
                'date' => $date,
            ]);

            return;
        }

        abort(403, 'Buổi học ngày '.Carbon::parse($date)->format('d/m/Y').' đã kết thúc quá '
            .(int) config('training_schedule.edit_grace_hours', 48)
            .' giờ ân hạn — chỉ Super Admin sửa được.');
    }

    public static function ensureSubjectInScope(Subject|int $subject, ?User $user = null): void
    {
        $user = self::user($user);
        self::ensureValidRoleConfiguration($user);
        if (! self::isFacultyManager($user)) {
            return;
        }

        $model = $subject instanceof Subject ? $subject : Subject::query()->findOrFail($subject);
        abort_unless(
            TrainingDept::subjectBelongsToFaculty($model, $user),
            403,
            'Môn học không thuộc phạm vi khoa được phân công.'
        );
    }

    public static function ensureInstructorInScope(Instructor|int $instructor, ?User $user = null): void
    {
        $user = self::user($user);
        if (! self::isFacultyManager($user)) {
            return;
        }

        $model = $instructor instanceof Instructor
            ? $instructor
            : Instructor::query()->findOrFail($instructor);

        abort_unless(
            $user?->unit_id && (int) $model->unit_id === (int) $user->unit_id,
            403,
            'Giảng viên không thuộc phạm vi khoa được phân công.'
        );
    }

    public static function isFacultyManager(?User $user = null): bool
    {
        return self::scope($user) === self::SCOPE_FACULTY;
    }

    public static function unitIsTrainingOffice(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user?->unit) {
            return false;
        }

        if (self::unitFunctionalType($user->unit) === Unit::FUNCTIONAL_TRAINING_OFFICE) {
            return true;
        }

        if (! $user->hasRole(ManagementRole::LEGACY_MANAGER)) {
            return false;
        }

        $code = strtoupper(trim((string) $user->unit->code));
        $name = mb_strtolower(trim((string) $user->unit->name));

        return $code === TrainingDept::UNIT_CODE_TRAINING_OFFICE
            || str_contains($name, 'đào tạo')
            || str_contains($name, 'dao tao');
    }

    public static function unitIsFaculty(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user?->unit) {
            return false;
        }

        $hasValidConfiguredFaculty = self::unitFunctionalType($user->unit) === Unit::FUNCTIONAL_FACULTY
            && self::facultyCodeForUnit($user->unit) !== null;

        if ($user->hasRole(ManagementRole::FACULTY_SCHEDULE_MANAGER)) {
            return $hasValidConfiguredFaculty;
        }

        if ($hasValidConfiguredFaculty) {
            return true;
        }

        if (! $user->hasRole(ManagementRole::LEGACY_MANAGER)) {
            return false;
        }

        return in_array(strtoupper(trim((string) $user->unit->code)), TrainingDept::FACULTY_CODES, true);
    }

    public static function facultyCode(?User $user = null): ?string
    {
        $user = self::user($user);
        if (! self::unitIsFaculty($user)) {
            return null;
        }

        return self::facultyCodeForUnit($user?->unit);
    }

    public static function unitFunctionalType(?Unit $unit): ?string
    {
        if (! $unit) {
            return null;
        }

        $configured = (string) ($unit->functional_type ?? '');
        if (in_array($configured, [Unit::FUNCTIONAL_TRAINING_OFFICE, Unit::FUNCTIONAL_FACULTY, Unit::FUNCTIONAL_APPROVAL_AGENCY], true)) {
            return $configured;
        }

        $code = strtoupper(trim((string) ($unit->code ?? '')));
        $name = mb_strtolower(trim((string) ($unit->name ?? '')));
        if ($code === TrainingDept::UNIT_CODE_TRAINING_OFFICE || str_contains($name, 'đào tạo') || str_contains($name, 'dao tao')) {
            return Unit::FUNCTIONAL_TRAINING_OFFICE;
        }
        if (in_array($code, TrainingDept::FACULTY_CODES, true)) {
            return Unit::FUNCTIONAL_FACULTY;
        }

        return $configured !== '' ? $configured : Unit::FUNCTIONAL_OTHER;
    }

    /**
     * Mã khoa của đơn vị — không còn giới hạn cứng vào danh sách K1-K8.
     * Khoa mới (K9, hoặc quy ước khác) dùng được ngay, miễn đơn vị đã đánh
     * dấu functional_type = Khoa (điều kiện đó nằm ở phía gọi, ví dụ
     * unitIsFaculty()). Sprint 44 §2.2.
     */
    public static function facultyCodeForUnit(?Unit $unit): ?string
    {
        if (! $unit) {
            return null;
        }
        $code = strtoupper(trim((string) ($unit->faculty_code ?: $unit->code)));

        return $code !== '' ? $code : null;
    }

    public static function label(?User $user = null): string
    {
        $user = self::user($user);

        return match (self::scope($user)) {
            self::SCOPE_SYSTEM => 'Quản lý toàn hệ thống',
            self::SCOPE_TRAINING_OFFICE => 'Phòng Đào tạo — quản lý khung lịch',
            self::SCOPE_FACULTY => sprintf(
                'Khoa %s — phân công bài học và giảng viên trong khoa',
                self::facultyCode($user) ?: ($user?->unit?->name ?? '')
            ),
            default => 'Không có phạm vi quản lý lịch đào tạo',
        };
    }
}
