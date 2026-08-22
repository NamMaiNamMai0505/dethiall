<?php

namespace Modules\Instructor\Providers;

use Illuminate\Support\ServiceProvider;

class InstructorServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'instructor');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
