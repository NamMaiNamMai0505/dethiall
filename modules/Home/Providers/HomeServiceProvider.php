<?php

namespace Modules\Home\Providers;

use Illuminate\Support\ServiceProvider;

class HomeServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Home';
    protected string $moduleNameLower = 'home';

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
