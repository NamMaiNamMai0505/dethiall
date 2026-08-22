<?php

namespace Modules\ScheduleDetail\Providers;

use Illuminate\Support\ServiceProvider;

class ScheduleDetailServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'scheduledetail');
    }
}
