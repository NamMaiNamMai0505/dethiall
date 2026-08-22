<?php

namespace Modules\EssayExam\Providers;

use Illuminate\Support\ServiceProvider;

class EssayExamServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Views', 'essay-exam');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
