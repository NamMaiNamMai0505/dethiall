<?php

namespace App\Providers;

use App\Observers\TrashModelObserver;
use App\Support\ApprovalAgency;
use App\Support\TrashRegistry;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module service providers
        $this->app->register(\Modules\Authentication\Providers\AuthenticationServiceProvider::class);
        $this->app->register(\Modules\Home\Providers\HomeServiceProvider::class);
        $this->app->register(\Modules\Dashboard\Providers\DashboardServiceProvider::class);
        $this->app->register(\Modules\Specialization\Providers\SpecializationServiceProvider::class);
        $this->app->register(\Modules\Subject\Providers\SubjectServiceProvider::class);
        $this->app->register(\Modules\TrainingSchedule\Providers\TrainingScheduleServiceProvider::class);
        $this->app->register(\Modules\Instructor\Providers\InstructorServiceProvider::class);
        $this->app->register(\Modules\Unit\Providers\UnitServiceProvider::class);
        $this->app->register(\Modules\User\Providers\UserServiceProvider::class);
        $this->app->register(\Modules\Class\Providers\ClassServiceProvider::class);
        $this->app->register(\Modules\Building\Providers\BuildingServiceProvider::class);
        $this->app->register(\Modules\TeachingAssignment\Providers\TeachingAssignmentServiceProvider::class);
        $this->app->register(\Modules\Classroom\Providers\ClassroomServiceProvider::class);
        $this->app->register(\Modules\ScheduleDetail\Providers\ScheduleDetailServiceProvider::class);
        $this->app->register(\Modules\InstructorSchedule\Providers\InstructorScheduleServiceProvider::class);
        $this->app->register(\Modules\Student\Providers\StudentServiceProvider::class);
        $this->app->register(\Modules\StudentSchedule\Providers\StudentScheduleServiceProvider::class);
        $this->app->register(\Modules\EssayExam\Providers\EssayExamServiceProvider::class);
        $this->app->register(\Modules\Inventory\Providers\InventoryServiceProvider::class);
        $this->app->register(\Modules\LeaveManagement\Providers\LeaveManagementServiceProvider::class);
        $this->app->register(\Modules\StandardHours\Providers\StandardHoursServiceProvider::class);
        $this->app->register(\Modules\Trash\Providers\TrashServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force tất cả URL sinh ra bằng HTTPS trên môi trường Production
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Super-admin bypass mọi permission check (tránh 403/500 khi thiếu permission mới sau deploy)
        Gate::before(function ($user, $ability) {
            try {
                if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                    return true;
                }
            } catch (\Throwable) {
                // Bỏ qua nếu Spatie cache/permission chưa sẵn sàng
            }

            if ($user instanceof \App\Models\User) {
                $approvalDecision = ApprovalAgency::permissionDecision($user, (string) $ability);
                // Spatie đăng ký Gate::before trước provider này. Callback tại
                // đây dùng để cấp quyền động; quyết định từ chối được thực thi
                // trong PermissionCheck và các policy nghiệp vụ chuyên biệt.
                if ($approvalDecision === true) {
                    return true;
                }
            }

            return null;
        });

        // Pagination UI thống nhất toàn hệ thống (brand + chevron)
        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');
        Paginator::useTailwind();

        // Register Blade directives
        $this->registerBladeDirectives();
        $this->registerTrashObservers();
    }

    /**
     * Soft-deleted models log into trash for super-admin / manager recovery.
     */
    protected function registerTrashObservers(): void
    {
        // Không gắn observer nếu bảng trash_logs chưa migrate (tránh 500 khi xóa bản ghi)
        try {
            if (! Schema::hasTable('trash_logs')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        foreach (TrashRegistry::modelClasses() as $modelClass) {
            if (class_exists($modelClass) && TrashRegistry::usesSoftDeletes($modelClass)) {
                $modelClass::observe(TrashModelObserver::class);
            }
        }
    }

    /**
     * Register custom Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // Safe permission checks — không ném 500 khi permission chưa sync vào DB
        Blade::directive('canPermission', function ($permission) {
            return "<?php if(\\App\\Support\\PermissionCheck::userCan({$permission})): ?>";
        });

        Blade::directive('endcanPermission', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('hasRole', function ($role) {
            return "<?php if(auth()->check() && (function (\$r) { try { return auth()->user()->hasRole(\$r); } catch (\\Throwable \$e) { return false; } })({$role})): ?>";
        });

        Blade::directive('endhasRole', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('hasAnyRole', function ($roles) {
            return "<?php if(auth()->check() && (function (\$r) { try { return auth()->user()->hasAnyRole(\$r); } catch (\\Throwable \$e) { return false; } })({$roles})): ?>";
        });

        Blade::directive('endhasAnyRole', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('hasPermission', function ($permission) {
            return "<?php if(\\App\\Support\\PermissionCheck::userCan({$permission})): ?>";
        });

        Blade::directive('endhasPermission', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('module', function ($expression) {
            [$module, $action] = explode(',', str_replace(['(', ')', "'", '"'], '', $expression));
            $module = trim($module);
            $action = trim($action);
            $perm = "{$action}.{$module}";

            return "<?php if(\\App\\Support\\PermissionCheck::userCan('{$perm}')): ?>";
        });

        Blade::directive('endmodule', function () {
            return '<?php endif; ?>';
        });
    }
}
