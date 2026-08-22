<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SubjectCodePrefix;

/**
 * Phân cấp lịch đào tạo theo đơn vị gắn trên tài khoản (users.unit_id):
 *
 * - Phòng đào tạo (unit code PHONG_DT / tên chứa "đào tạo"):
 *   → Chỉ việc của Phòng ĐT: xếp khung lịch tổng thể (tháng/ngày/môn theo lớp).
 *   → Không làm việc “phân công chi tiết GV/bài” theo khoa.
 *
 * - Khoa (role quản lý lịch Khoa + unit được phân loại Khoa):
 *   → Chỉ quản / phân bổ bài học + GV cho **môn thuộc khoa** (mã môn kết thúc …K7, …K8…).
 *   → Trong 1 lớp: mỗi khoa chỉ làm phần môn của mình (PDOT đã xếp khung theo ngành/lớp).
 *   → GV chỉ lấy trong phạm vi unit khoa.
 *
 * - super-admin: full (bypass).
 *
 * Role `manager` cũ vẫn được hỗ trợ tạm theo unit trong giai đoạn chuyển đổi.
 */
class TrainingDept
{
    public const UNIT_CODE_TRAINING_OFFICE = 'PHONG_DT';

    /** Mã khoa chuẩn (khớp hậu tố mã môn). */
    public const FACULTY_CODES = ['K1', 'K2', 'K3', 'K4', 'K5', 'K6', 'K7', 'K8'];

    public static function user(?User $user = null): ?User
    {
        $user = $user ?: Auth::user();

        if ($user && ! $user->relationLoaded('unit') && method_exists($user, 'unit')) {
            $user->loadMissing('unit');
        }

        return $user;
    }

    /**
     * Tài khoản thuộc Phòng đào tạo (theo unit).
     * Super-admin được coi là có quyền PDOT để vận hành hệ thống.
     */
    public static function isTrainingOffice(?User $user = null): bool
    {
        return TrainingScheduleAccess::canManageSkeleton($user);
    }

    /**
     * Chỉ kiểm tra unit, không tính super-admin.
     */
    public static function unitIsTrainingOffice(?User $user = null): bool
    {
        return TrainingScheduleAccess::unitIsTrainingOffice($user);
    }

    /**
     * Manager thuộc khoa (không phải Phòng ĐT, không phải super-admin).
     */
    public static function isFacultyManager(?User $user = null): bool
    {
        return TrainingScheduleAccess::isFacultyManager($user);
    }

    /**
     * Phòng ĐT (hoặc super-admin): xếp khung lịch (tháng/ngày/môn/lớp).
     */
    public static function canManageScheduleSkeleton(?User $user = null): bool
    {
        return TrainingScheduleAccess::canManageSkeleton($user);
    }

    /**
     * Khoa: phân công bài học + GV trong phạm vi khoa.
     * Super-admin / PDOT cũng được (override vận hành).
     */
    public static function canAssignFacultySchedule(?User $user = null): bool
    {
        return TrainingScheduleAccess::canAssignFacultySchedule($user);
    }

    /**
     * Mã khoa K1–K8 từ unit tài khoản (null nếu không phải khoa chuẩn).
     */
    public static function facultyUnitCode(?User $user = null): ?string
    {
        return TrainingScheduleAccess::facultyCode($user);
    }

    /**
     * Môn có hậu tố mã khoa trùng unit của user?
     */
    public static function subjectBelongsToFaculty(Subject|string|null $subjectOrCode, ?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        // Super-admin / PDOT: full
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }
        if (self::canManageScheduleSkeleton($user)) {
            return true;
        }

        $facultyCode = self::facultyUnitCode($user);
        if (! $facultyCode) {
            return false;
        }

        if ($subjectOrCode instanceof Subject && $subjectOrCode->is_special_activity) {
            return true;
        }

        // Subject::faculty_code (accessor) đã tự ưu tiên phân công tường minh
        // và rơi về hậu tố mã môn khi chưa gán — xem Subject::getFacultyCodeAttribute().
        // applyFacultyCodeScope() dùng cùng thứ tự ưu tiên ở tầng SQL.
        if ($subjectOrCode instanceof Subject) {
            $resolved = $subjectOrCode->faculty_code;

            return $resolved !== null && strtoupper($resolved) === $facultyCode;
        }

        return SubjectCodePrefix::facultyCodeFromSubjectCode((string) $subjectOrCode) === $facultyCode;
    }

    /**
     * Scope query Subject theo khoa của user (manager khoa).
     * PDOT / super-admin: không lọc.
     */
    public static function applySubjectFacultyScope(Builder|Relation $query, ?User $user = null): Builder|Relation
    {
        $user = self::user($user);
        if (! $user) {
            return $query;
        }

        $scope = TrainingScheduleAccess::scope($user);
        if (
            $scope === TrainingScheduleAccess::SCOPE_NONE
            && $user->hasAnyRole([
                ManagementRole::LEGACY_MANAGER,
                ManagementRole::FACULTY_SCHEDULE_MANAGER,
                ManagementRole::TRAINING_OFFICE_MANAGER,
            ])
        ) {
            return $query->whereRaw('1 = 0');
        }

        if ($scope !== TrainingScheduleAccess::SCOPE_FACULTY) {
            return $query;
        }

        $facultyCode = self::facultyUnitCode($user);
        if (! $facultyCode) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($scoped) use ($facultyCode) {
            SubjectCodePrefix::applyFacultyCodeScope($scoped, $facultyCode);
            $scoped->orWhere('is_special_activity', true);
        });
    }

    /**
     * ID các môn thuộc khoa user (cache theo request).
     *
     * @return list<int>|null null = không giới hạn
     */
    public static function facultySubjectIds(?User $user = null): ?array
    {
        $user = self::user($user);
        if (! $user || ! self::isFacultyManager($user)) {
            return null;
        }

        static $cache = [];
        $key = (string) ($user->id.'|'.($user->unit_id ?? 0));
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $q = Subject::query();
        self::applySubjectFacultyScope($q, $user);
        $cache[$key] = $q->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $cache[$key];
    }

    /**
     * Nhãn hiển thị trên UI hub / form.
     */
    public static function scopeLabel(?User $user = null): string
    {
        return TrainingScheduleAccess::label($user);
    }
}
