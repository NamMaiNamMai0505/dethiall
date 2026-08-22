<?php

namespace Modules\Lms\Support;

use App\Models\User;
use App\Support\TrainingDept;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Models\LmsCourseMember;
use Modules\Lms\Services\LmsCourseService;

/**
 * Phân tách portal:
 * - Admin/manager (PDOT, Khoa, super-admin): layouts.admin + /lms hub
 * - Giảng viên: /lms/gv (portal dạy)
 * - Học viên: /lms/hoc
 */
class LmsAccess
{
    public static function user(?User $user = null): ?User
    {
        return $user ?: Auth::user();
    }

    /** Dùng shell admin LMS (không phải trang học viên/GV) */
    public static function usesAdminShell(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'isManager') && $user->isManager()) {
            return true;
        }

        if (TrainingDept::unitIsTrainingOffice($user)) {
            return true;
        }

        return false;
    }

    /** Portal học viên / giảng viên (giao diện tách) */
    public static function usesLearnerShell(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        return ! self::usesAdminShell($user);
    }

    public static function isInstructorUser(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        return (method_exists($user, 'isInstructor') && $user->isInstructor())
            || $user->user_type === 'instructor'
            || (method_exists($user, 'hasRole') && $user->hasRole('instructor'));
    }

    public static function isStudentUser(?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        return (method_exists($user, 'isStudent') && $user->isStudent())
            || $user->user_type === 'student';
    }

    /**
     * Entry sau đăng nhập / nút LMS trên home.
     */
    public static function entryRouteName(?User $user = null): string
    {
        $user = self::user($user);
        if (! $user) {
            return 'home';
        }

        if (self::usesAdminShell($user)) {
            return 'lms.hub';
        }

        if (self::isInstructorUser($user) && ($user->can('lms.index') || $user->can('lms.edit'))) {
            return 'lms.teach.home';
        }

        return 'lms.learn.home';
    }

    public static function entryUrl(?User $user = null): string
    {
        return route(self::entryRouteName($user));
    }

    /**
     * GV (hoặc admin) được “dạy / quản lý” khóa này trong portal teach.
     */
    public static function canTeachCourse(LmsCourse $course, ?User $user = null): bool
    {
        $user = self::user($user);
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (TrainingDept::isFacultyManager($user)) {
            return app(LmsCourseService::class)
                ->queryForUser($user)
                ->where('lms_courses.id', $course->id)
                ->exists();
        }

        if (TrainingDept::unitIsTrainingOffice($user) || $user->can('lms.manage')) {
            return true;
        }

        if (! $user->can('lms.edit') && ! $user->can('lms.show')) {
            return false;
        }

        if ($user->instructor_id && (int) $course->instructor_id === (int) $user->instructor_id) {
            return true;
        }

        return LmsCourseMember::query()
            ->where('lms_course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', LmsCourseMember::STATUS_ACTIVE)
            ->whereIn('role', [
                LmsCourseMember::ROLE_LECTURER,
                LmsCourseMember::ROLE_ASSISTANT,
                'lecturer',
                'assistant',
            ])
            ->exists();
    }

    /** Request đang ở mode teach (portal GV). */
    public static function isTeachMode(): bool
    {
        if (request()->routeIs('lms.teach.*')) {
            return true;
        }

        return request()->query('mode') === 'teach';
    }
}
