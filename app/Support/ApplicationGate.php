<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Kiểm tra quyền của một ứng dụng trong ApplicationRegistry.
 *
 * Dùng chung cho LMS và Quản lý điểm: mỗi màn hình gác bằng quyền của chính
 * ứng dụng đó, đồng thời vẫn chấp nhận quyền tổng cũ của phân hệ trong giai
 * đoạn chuyển đổi để vai trò chưa cập nhật không bị khoá ngoài.
 */
final class ApplicationGate
{
    /**
     * Quyền tổng cũ tương đương cho từng hành động, theo phân hệ.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const LEGACY_FALLBACK = [
        'lms' => [
            ApplicationRegistry::ACTION_VIEW => ['lms.index', 'lms.show'],
            ApplicationRegistry::ACTION_CREATE => ['lms.create'],
            ApplicationRegistry::ACTION_EDIT => ['lms.edit'],
            ApplicationRegistry::ACTION_DELETE => ['lms.delete'],
            ApplicationRegistry::ACTION_APPROVE => ['lms.manage'],
            ApplicationRegistry::ACTION_EXPORT => ['lms.manage'],
        ],
        'grades' => [
            ApplicationRegistry::ACTION_VIEW => ['grades.index', 'grades.show'],
            ApplicationRegistry::ACTION_CREATE => ['grades.create'],
            ApplicationRegistry::ACTION_EDIT => ['grades.edit'],
            ApplicationRegistry::ACTION_DELETE => ['grades.delete'],
            ApplicationRegistry::ACTION_APPROVE => ['grades.approve', 'grades.manage'],
            ApplicationRegistry::ACTION_EXPORT => ['grades.manage'],
        ],
    ];

    /**
     * Danh sách quyền chấp nhận cho một hành động của ứng dụng.
     *
     * @return list<string>
     */
    public static function abilities(string $applicationKey, string $action): array
    {
        $abilities = ApplicationRegistry::permissionNamesFor($applicationKey, $action);

        $application = ApplicationRegistry::applications()[$applicationKey] ?? null;
        if ($application !== null) {
            $legacy = self::LEGACY_FALLBACK[$application['subsystem']][$action] ?? [];
            $abilities = array_merge($abilities, $legacy);
        }

        return array_values(array_unique($abilities));
    }

    public static function allows(?Authorizable $user, string $applicationKey, string $action): bool
    {
        if ($user === null) {
            return false;
        }

        foreach (self::abilities($applicationKey, $action) as $ability) {
            if ($user->can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Đúng khi tài khoản dùng được ít nhất một trong các hành động.
     *
     * @param  list<string>  $actions
     */
    public static function allowsAny(?Authorizable $user, string $applicationKey, array $actions): bool
    {
        foreach ($actions as $action) {
            if (self::allows($user, $applicationKey, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Middleware gác một hành động của ứng dụng.
     */
    public static function middleware(string $applicationKey, string $action): \Closure
    {
        return function ($request, $next) use ($applicationKey, $action) {
            abort_unless(self::allows($request->user(), $applicationKey, $action), 403);

            return $next($request);
        };
    }
}
