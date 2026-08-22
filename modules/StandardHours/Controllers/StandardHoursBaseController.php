<?php

namespace Modules\StandardHours\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\ApplicationRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\ViewException;

abstract class StandardHoursBaseController extends ModuleBaseController
{
    protected bool $useGenericModulePermissions = false;

    protected bool $allowInstructorAccess = false;

    public function __construct()
    {
        parent::__construct();

        // Mỗi ứng dụng Giờ chuẩn tự gác bằng quyền của chính nó (ApplicationRegistry).
        // Quyền tổng `standard-hours.index|create|edit` KHÔNG còn mở được ứng dụng
        // khác — trước đây một role chỉ cần quyền tổng là dùng được cả 15 ứng dụng.
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_VIEW))
            ->only(['index', 'show', 'guide']);
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_CREATE))
            ->only(['create', 'store']);
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_EDIT))
            ->only(['edit', 'update']);
        $this->middleware($this->authorizeApplication(ApplicationRegistry::ACTION_DELETE))
            ->only(['destroy', 'restore']);

        if (! $this->allowInstructorAccess) {
            $this->middleware(function ($request, $next) {
                if (auth()->user()?->hasRole('instructor')) {
                    abort(403, 'Bạn không có quyền truy cập chức năng này.');
                }

                return $next($request);
            });
        }
    }

    /**
     * Middleware kiểm tra một hành động của ứng dụng hiện tại.
     */
    protected function authorizeApplication(string $action): \Closure
    {
        $abilities = $this->abilitiesFor($action);

        return function ($request, $next) use ($abilities) {
            $user = $request->user();
            $allowed = $user !== null && $abilities !== [] && collect($abilities)->contains(
                fn (string $ability) => $user->can($ability)
            );
            abort_unless($allowed, 403);

            return $next($request);
        };
    }

    /**
     * Quyền chấp nhận cho một hành động: quyền chi tiết theo registry, cộng
     * quyền gộp `<ứng dụng>.manage` cũ (vẫn thuộc đúng ứng dụng, không phải
     * quyền tổng của cả phân hệ) để role chưa chuyển đổi không bị gãy.
     *
     * @return list<string>
     */
    protected function abilitiesFor(string $action): array
    {
        $abilities = ApplicationRegistry::permissionNamesFor($this->permissionPrefix(), $action);

        if (in_array($action, [
            ApplicationRegistry::ACTION_CREATE,
            ApplicationRegistry::ACTION_EDIT,
            ApplicationRegistry::ACTION_DELETE,
        ], true)) {
            $abilities[] = $this->permissionPrefix().'.manage';
        }

        return array_values(array_unique($abilities));
    }

    protected function permissionPrefix(): string
    {
        return match (class_basename(static::class)) {
            'HubController' => 'standard-hours',
            'MyResultController' => 'standard-hours.declarations',
            'ObjectTypeController' => 'standard-hours.object-types',
            'PositionController' => 'standard-hours.positions',
            'DepartmentController' => 'standard-hours.departments',
            'DepartmentOvertimeController' => 'standard-hours.department-overtime',
            'NormReductionController' => 'standard-hours.norm-reductions',
            'ConversionCategoryController' => 'standard-hours.conversion-categories',
            'ResearchCategoryController' => 'standard-hours.research-categories',
            'ConversionController' => 'standard-hours.conversion-records',
            'ResearchController' => 'standard-hours.research-records',
            'ExternalActivityController' => 'standard-hours.external-activities',
            'CalculationController' => 'standard-hours.calculations',
            'ReportController' => 'standard-hours.reports',
            'HourExchangeController' => 'standard-hours.hour-exchanges',
            'SettingsController' => 'standard-hours.settings',
            default => 'standard-hours',
        };
    }

    protected function getModuleName(string $controllerName): string
    {
        return 'standard-hours';
    }

    /**
     * Catch missing tables / view errors → friendly page instead of bare 500.
     */
    public function callAction($method, $parameters)
    {
        try {
            return parent::callAction($method, $parameters);
        } catch (QueryException $e) {
            Log::error('StandardHours query failed', [
                'controller' => static::class,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            if ($this->looksLikeMissingTable($e)) {
                return response()->view('standardhours::errors.setup-required', [
                    'message' => 'Cơ sở dữ liệu module Giờ chuẩn chưa đầy đủ (thiếu bảng). Chạy migration trên server.',
                    'detail' => $e->getMessage(),
                ], 503);
            }

            return response()->view('standardhours::errors.setup-required', [
                'message' => 'Lỗi truy vấn dữ liệu giờ chuẩn.',
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (ViewException $e) {
            Log::error('StandardHours view failed', [
                'controller' => static::class,
                'method' => $method,
                'error' => $e->getMessage(),
                'previous' => $e->getPrevious()?->getMessage(),
            ]);

            $prev = $e->getPrevious();
            if ($prev instanceof QueryException && $this->looksLikeMissingTable($prev)) {
                return response()->view('standardhours::errors.setup-required', [
                    'message' => 'Cơ sở dữ liệu module Giờ chuẩn chưa đầy đủ (thiếu bảng). Chạy migration trên server.',
                    'detail' => $prev->getMessage(),
                ], 503);
            }

            // Relation null / undefined var trong blade → thông báo rõ
            return response()->view('standardhours::errors.setup-required', [
                'message' => 'Không hiển thị được trang chức năng giờ chuẩn.',
                'detail' => config('app.debug')
                    ? ($e->getMessage().($prev ? ' | '.$prev->getMessage() : ''))
                    : null,
            ], 500);
        }
    }

    protected function looksLikeMissingTable(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, "doesn't exist")
            || str_contains($msg, 'Base table or view not found')
            || str_contains($msg, 'no such table');
    }

    /**
     * @param  list<string>  $tables
     */
    protected function tablesReady(array $tables): bool
    {
        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
