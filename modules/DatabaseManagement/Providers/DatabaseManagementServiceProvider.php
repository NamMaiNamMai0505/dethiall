<?php

namespace Modules\DatabaseManagement\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseManagementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'database-management');
    }
}
