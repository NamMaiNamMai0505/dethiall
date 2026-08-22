<?php

namespace Modules\TeachingAssignment\Providers;

use Illuminate\Support\ServiceProvider;

class TeachingAssignmentServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'teaching-assignment');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}

