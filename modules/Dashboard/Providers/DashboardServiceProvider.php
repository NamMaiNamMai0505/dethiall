<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Dashboard';
    protected string $moduleNameLower = 'dashboard';

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
    }

    public function register(): void
    {
        //
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', $this->moduleNameLower);
    }
}
