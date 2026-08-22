<?php

namespace Modules\Building\Providers;

use Illuminate\Support\ServiceProvider;

class BuildingServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', 'building');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        
    }
}
