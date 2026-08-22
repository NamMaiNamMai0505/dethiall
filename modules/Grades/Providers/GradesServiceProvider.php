<?php

namespace Modules\Grades\Providers;

use Illuminate\Support\ServiceProvider;

class GradesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'grades');
    }

    public function register(): void
    {
        //
    }
}
