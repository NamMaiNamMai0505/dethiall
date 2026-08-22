<?php

namespace Modules\TemplateManagement\Providers;

use Illuminate\Support\ServiceProvider;

class TemplateManagementServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes và views cho module
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', strtolower('TemplateManagement'));
    }
}