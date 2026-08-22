<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckMultiplePermissions;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\RecordManagerActivity;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Authentication\Middleware\RedirectIfAuthenticated;
use Modules\Subject\Support\ImportRuntime;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * TrustProxies — P0 điểm danh Wi‑Fi (IP client đúng sau reverse proxy / LB).
         * .env: TRUSTED_PROXIES=*  hoặc  10.0.0.1,10.0.0.2
         * Headers: X-Forwarded-For, X-Forwarded-Proto, X-Forwarded-Port, X-Forwarded-Host
         */
        $trusted = env('TRUSTED_PROXIES');
        if ($trusted !== null && trim((string) $trusted) !== '') {
            $at = trim((string) $trusted) === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', (string) $trusted))));
            $middleware->trustProxies(
                at: $at,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        $middleware->web(append: [
            SecurityHeaders::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            RecordManagerActivity::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'permission' => CheckPermission::class,
            'role' => CheckRole::class,
            'permission.multiple' => CheckMultiplePermissions::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Weekly database backup to s3 on Sundays at 0:00
        $schedule->command('backup:run --only-db --only-to-disk=s3')
            ->weekly()
            ->sundays()
            ->at('00:00')
            ->emailOutputOnFailure(explode(',', env('BACKUP_NOTIFICATIONS_MAIL', 'hc2@cdhc2.edu.vn')));

        // Weekly database backup on Sundays at 0:00
        $schedule->command('backup:run --only-db')
            ->weekly()
            ->sundays()
            ->at('00:00')
            ->emailOutputOnFailure(explode(',', env('BACKUP_NOTIFICATIONS_MAIL', 'hc2@cdhc2.edu.vn')));

        // Clean old backups weekly on Sundays at 0:00
        $schedule->command('backup:clean')
            ->weekly()
            ->sundays()
            ->at('2:00');

        // Khởi tạo/cập nhật khóa LMS theo lịch trước khi đồng bộ roster.
        $schedule->command('lms:provision-courses')
            ->dailyAt('01:10')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lms-provision-courses.log'));

        // Đồng bộ roster LMS hằng ngày.
        $schedule->command('lms:sync-members --published')
            ->dailyAt('01:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lms-sync-members.log'));

        // Sprint 9 T8: retention file bài nộp (hàng tháng, dry log + prune)
        $schedule->command('lms:prune-submissions --months=24')
            ->monthlyOn(1, '03:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lms-prune-submissions.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sprint 8 G11: trang 403 riêng cho portal LMS
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }
            $path = ltrim($request->path(), '/');
            if (str_starts_with($path, 'lms/') || $path === 'lms') {
                return response()->view('errors.lms-403', [
                    'exception' => $e,
                ], 403);
            }

            return null;
        });

        // Import Excel luôn trả JSON có mã tra cứu. Nhờ vậy exception xảy ra ở
        // middleware/validation trước controller không còn biến thành trang 500
        // HTML mà giao diện không thể giải thích cho người dùng.
        $exceptions->render(function (Throwable $e, $request) {
            $isLessonImport = $request->is('subjects/lessons-manage/import')
                || $request->is('subjects/lessons/import');

            if (! $isLessonImport || ! $request->isMethod('POST') || ! $request->expectsJson()) {
                return null;
            }

            $importId = ImportRuntime::resolveId(
                $request->header('X-Import-ID')
            );
            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : ($e instanceof ValidationException ? 422 : 500);

            try {
                Log::error('Unhandled lesson import request failure.', [
                    'import_id' => $importId,
                    'path' => $request->path(),
                    'status' => $status,
                    'user_id' => $request->user()?->id,
                    'exception' => $e,
                ]);
            } catch (Throwable) {
                // Logging must never replace the JSON fallback.
            }

            $payload = [
                'success' => false,
                'message' => $status >= 500
                    ? 'Máy chủ không hoàn tất được import. Vui lòng cung cấp mã lỗi '.$importId.' cho quản trị viên.'
                    : ImportRuntime::safeExceptionMessage($e),
            ];

            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors();
            }

            return ImportRuntime::jsonResponse(
                $payload,
                $status,
                $importId
            );
        });
    })->create();
