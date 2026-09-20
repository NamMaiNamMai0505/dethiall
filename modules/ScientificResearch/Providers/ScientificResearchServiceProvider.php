<?php

namespace Modules\ScientificResearch\Providers;

use Illuminate\Support\ServiceProvider;

class ScientificResearchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'scientific-research');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
