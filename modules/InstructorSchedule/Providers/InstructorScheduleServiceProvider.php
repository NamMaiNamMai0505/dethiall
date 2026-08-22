<?php

namespace Modules\InstructorSchedule\Providers;

use Illuminate\Support\ServiceProvider;

class InstructorScheduleServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'instructorschedule');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
