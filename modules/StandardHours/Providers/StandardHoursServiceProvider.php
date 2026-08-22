<?php

namespace Modules\StandardHours\Providers;

use Illuminate\Support\ServiceProvider;

class StandardHoursServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'standardhours');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
