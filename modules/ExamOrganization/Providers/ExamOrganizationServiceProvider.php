<?php
namespace Modules\ExamOrganization\Providers;
use Illuminate\Support\ServiceProvider;
class ExamOrganizationServiceProvider extends ServiceProvider { public function boot():void { $this->loadRoutesFrom(__DIR__.'/../Routes/web.php'); $this->loadViewsFrom(__DIR__.'/../Views','exam-organization'); $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations'); } }
