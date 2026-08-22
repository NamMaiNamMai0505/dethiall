<?php

namespace App\Support;

use App\Models\User;
use Modules\Unit\Models\Unit;

/**
 * Phân tuyến cơ quan phê duyệt theo đơn vị công tác.
 *
 * Đơn vị là nguồn quyết định phạm vi nghiệp vụ; role chỉ cung cấp giao diện
 * và nhóm quyền nền. Vì vậy thay người trong ban không cần sửa source code.
 */
final class ApprovalAgency
{
    public const ROLE = 'approval-agency';

    public const RESEARCH_UNIT_CODE = 'BKHQS';

    public const STANDARD_HOURS_GRADES_UNIT_CODE = 'BKT&ĐBCLGDĐT';

    /**
     * Quyền nền cấp tự động theo mã đơn vị, kể cả khi tài khoản chưa được
     * đồng bộ role sau khi chuyển đơn vị.
     */
    private const COMMON_STANDARD_HOURS_PERMISSIONS = [
        'dashboards.index',
        'standard-hours.index',
        'standard-hours.show',
        'standard-hours.approve',
        'standard-hours.view',
        'standard-hours.conversion-records.view',
        'standard-hours.conversion-records.approve',
        'standard-hours.research-records.view',
        'standard-hours.research-records.approve',
        'standard-hours.external-activities.view',
        'standard-hours.external-activities.approve',
        'standard-hours.calculations.view',
        'standard-hours.calculations.approve',
        'standard-hours.reports.view',
        'standard-hours.reports.export',
    ];

    private const STANDARD_HOURS_GRADES_PERMISSIONS = [
        'grades.index',
        'grades.show',
        'grades.manage',
        'grades.approve',
    ];

    public static function grantsPermission(?User $user, string $permission): bool
    {
        if (! $user || ! self::isApprovalAgencyUnit($user)) {
            return false;
        }

        if (in_array($permission, self::COMMON_STANDARD_HOURS_PERMISSIONS, true)) {
            return true;
        }

        return self::isStandardHoursGradesAgency($user)
            && in_array($permission, self::STANDARD_HOURS_GRADES_PERMISSIONS, true);
    }

    /**
     * Quyết định tường minh cho hai module nhạy cảm.
     *
     * Trả null với module ngoài phạm vi để Laravel tiếp tục xét role/policy.
     * Với Giờ chuẩn và Điểm, mã ban là quyết định cuối cùng nhằm tránh một
     * role cũ vô tình mở quyền chéo sau khi người dùng chuyển đơn vị.
     */
    public static function permissionDecision(?User $user, string $permission): ?bool
    {
        if (! $user || ! self::isApprovalAgencyUnit($user)) {
            return null;
        }

        if (
            ! str_starts_with($permission, 'standard-hours.')
            && ! str_starts_with($permission, 'grades.')
            && $permission !== 'dashboards.index'
        ) {
            return null;
        }

        return self::grantsPermission($user, $permission);
    }

    public static function isApprovalAgencyUnit(?User $user): bool
    {
        return self::isResearchAgency($user)
            || self::isStandardHoursGradesAgency($user);
    }

    public static function isResearchAgency(?User $user): bool
    {
        return self::unitCode($user) === self::normalizeUnitCode(self::RESEARCH_UNIT_CODE);
    }

    public static function isStandardHoursGradesAgency(?User $user): bool
    {
        return self::unitCode($user) === self::normalizeUnitCode(
            self::STANDARD_HOURS_GRADES_UNIT_CODE
        );
    }

    public static function canReviewResearch(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Hai ban chuyên trách là phạm vi đóng: Ban KT không được duyệt NCKH.
        if (self::isApprovalAgencyUnit($user)) {
            return self::isResearchAgency($user);
        }

        return self::legacyStandardHoursReviewer($user);
    }

    public static function canReviewProfessionalActivities(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Ban KHQS chỉ duyệt NCKH, không duyệt HĐCM/giờ chuẩn.
        if (self::isApprovalAgencyUnit($user)) {
            return self::isStandardHoursGradesAgency($user);
        }

        return self::legacyStandardHoursReviewer($user);
    }

    public static function canReviewAnnualStandardHours(?User $user): bool
    {
        return self::canReviewProfessionalActivities($user);
    }

    public static function canApproveGrades(?User $user): bool
    {
        return $user?->isSuperAdmin() === true
            || self::isStandardHoursGradesAgency($user);
    }

    public static function canAccessStandardHoursRoute(?User $user, ?string $routeName): bool
    {
        if (! $user || $user->isSuperAdmin() || ! self::isApprovalAgencyUnit($user)) {
            return true;
        }

        $routeName = (string) $routeName;

        if (self::isResearchAgency($user)) {
            return in_array($routeName, ['standard-hours.hub', 'standard-hours.guide'], true)
                || str_starts_with($routeName, 'standard-hours.research-records.')
                || in_array($routeName, [
                    'standard-hours.research-categories.index',
                    'standard-hours.research-categories.show',
                ], true);
        }

        return ! str_starts_with($routeName, 'standard-hours.research-records.')
            && ! str_starts_with($routeName, 'standard-hours.research-categories.')
            && ! str_starts_with($routeName, 'standard-hours.settings.research-rules')
            && $routeName !== 'standard-hours.reports.export-research'
            && $routeName !== 'standard-hours.legacy-research-norms';
    }

    public static function canAccessGradesRoute(?User $user): bool
    {
        return ! self::isResearchAgency($user) || $user?->isSuperAdmin() === true;
    }

    public static function ensureCanReviewResearch(?User $user): void
    {
        abort_unless(
            self::canReviewResearch($user),
            403,
            'Chỉ Ban Khoa học Quân sự được phê duyệt kê khai NCKH.'
        );
    }

    public static function ensureCanReviewProfessionalActivities(?User $user): void
    {
        abort_unless(
            self::canReviewProfessionalActivities($user),
            403,
            'Chỉ cơ quan KT&ĐBCLGDĐT được phê duyệt kê khai hoạt động chuyên môn.'
        );
    }

    public static function ensureCanReviewAnnualStandardHours(?User $user): void
    {
        abort_unless(
            self::canReviewAnnualStandardHours($user),
            403,
            'Chỉ cơ quan KT&ĐBCLGDĐT được phê duyệt kê khai giờ chuẩn.'
        );
    }

    public static function normalizeUnitCode(?string $code): string
    {
        $normalized = preg_replace('/[\s\-–—]+/u', '', trim((string) $code)) ?? '';

        return mb_strtoupper($normalized, 'UTF-8');
    }

    private static function unitCode(?User $user): string
    {
        if (! $user || ! $user->unit_id) {
            return '';
        }

        try {
            // Không dùng loadMissing('unit:id,code'): Gate::before chạy cho MỌI
            // permission check nên đây gần như luôn là nơi đầu tiên chạm vào
            // relation `unit` trong request. loadMissing với cột giới hạn sẽ
            // cache bản cụt đó vào $user->unit — mọi loadMissing('unit') đủ cột
            // gọi sau (TrainingScheduleAccess, TrainingDept…) đều bị Eloquent bỏ
            // qua vì relation coi như "đã load", khiến functional_type/faculty_code
            // rơi về null và mọi kiểm tra phạm vi khoa sai theo.
            if ($user->relationLoaded('unit')) {
                return self::normalizeUnitCode($user->unit?->code);
            }

            return self::normalizeUnitCode(
                Unit::query()->whereKey($user->unit_id)->value('code')
            );
        } catch (\Throwable) {
            return '';
        }
    }

    private static function legacyStandardHoursReviewer(User $user): bool
    {
        try {
            return ! $user->hasRole('instructor')
                && ($user->can('standard-hours.approve')
                    || $user->can('standard-hours.conversion-records.approve')
                    || $user->can('standard-hours.research-records.approve')
                    || $user->can('standard-hours.external-activities.approve'));
        } catch (\Throwable) {
            return false;
        }
    }
}
