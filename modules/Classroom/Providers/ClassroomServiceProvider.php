<?php

namespace Modules\Classroom\Providers;

use Illuminate\Support\ServiceProvider;

class ClassroomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', 'classroom');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
