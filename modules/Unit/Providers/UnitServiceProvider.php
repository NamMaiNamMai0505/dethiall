<?php

namespace Modules\Unit\Providers;

use Illuminate\Support\ServiceProvider;

class UnitServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'unit');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
