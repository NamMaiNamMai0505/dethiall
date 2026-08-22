<?php

namespace Modules;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Modules\Authentication\Providers\AuthenticationServiceProvider;
use Modules\Building\Providers\BuildingServiceProvider;
use Modules\Class\Providers\ClassServiceProvider;
use Modules\Classroom\Providers\ClassroomServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Home\Providers\HomeServiceProvider;
use Modules\Instructor\Providers\InstructorServiceProvider;
use Modules\InstructorSchedule\Providers\InstructorScheduleServiceProvider;
use Modules\EssayExam\Providers\EssayExamServiceProvider;
use Modules\ExamOrganization\Providers\ExamOrganizationServiceProvider;
use Modules\ScheduleDetail\Providers\ScheduleDetailServiceProvider;
use Modules\Specialization\Providers\SpecializationServiceProvider;
use Modules\Subject\Providers\SubjectServiceProvider;
use Modules\TeachingAssignment\Providers\TeachingAssignmentServiceProvider;
use Modules\TrainingSchedule\Providers\TrainingScheduleServiceProvider;
use Modules\Unit\Providers\UserServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;

    protected string $moduleNameLower;

    protected $providers = [
        AuthenticationServiceProvider::class,
        DashboardServiceProvider::class,
        HomeServiceProvider::class,
        SpecializationServiceProvider::class,
        SubjectServiceProvider::class,
        TrainingScheduleServiceProvider::class,
        InstructorServiceProvider::class,
        TeachingAssignmentServiceProvider::class,
        ScheduleDetailServiceProvider::class,
        InstructorScheduleServiceProvider::class,
        UserServiceProvider::class,
        ClassServiceProvider::class,
        ClassroomServiceProvider::class,
        BuildingServiceProvider::class,
        EssayExamServiceProvider::class,
        ExamOrganizationServiceProvider::class,
    ];

    public function __construct($app)
    {
        parent::__construct($app);
        $this->moduleNameLower = strtolower($this->moduleName);
    }

    public function boot(): void
    {
        // Chỉ load views, translations và config
        // Routes sẽ được load trong provider riêng của từng module
        $this->registerViews();
        $this->registerTranslations();
        $this->registerConfig();
    }

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    protected function registerRoutes(): void
    {
        $routesPath = __DIR__."/{$this->moduleName}/Routes";

        if (File::exists($routesPath.'/web.php')) {
            $this->loadRoutesFrom($routesPath.'/web.php');
        }

        if (File::exists($routesPath.'/api.php')) {
            $this->loadRoutesFrom($routesPath.'/api.php');
        }
    }

    protected function registerViews(): void
    {
        $viewPath = __DIR__."/{$this->moduleName}/Views";

        if (File::exists($viewPath)) {
            $this->loadViewsFrom($viewPath, $this->moduleNameLower);
        }
    }

    protected function registerTranslations(): void
    {
        $langPath = __DIR__."/{$this->moduleName}/Resources/lang";

        if (File::exists($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    protected function registerConfig(): void
    {
        $configPath = __DIR__."/{$this->moduleName}/Config/config.php";

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    protected function getModulePath(string $path = ''): string
    {
        return __DIR__."/{$this->moduleName}".($path ? "/{$path}" : '');
    }
}
