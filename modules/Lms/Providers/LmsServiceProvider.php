<?php

namespace Modules\Lms\Providers;

use Illuminate\Support\ServiceProvider;

class LmsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Lms';

    protected string $moduleNameLower = 'lms';

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', $this->moduleNameLower);
        $this->loadMigrationsFrom(database_path('migrations'));
    }

    public function register(): void
    {
        //
    }
}
