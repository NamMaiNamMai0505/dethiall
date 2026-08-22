<?php

namespace Modules\StudentSchedule\Providers;

use Illuminate\Support\ServiceProvider;

class StudentScheduleServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'studentschedule');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
