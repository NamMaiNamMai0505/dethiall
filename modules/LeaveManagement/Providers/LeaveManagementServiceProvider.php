<?php
namespace Modules\LeaveManagement\Providers;
use Illuminate\Support\ServiceProvider;
class LeaveManagementServiceProvider extends ServiceProvider { public function boot():void{$this->loadRoutesFrom(__DIR__.'/../Routes/web.php');$this->loadViewsFrom(__DIR__.'/../Views','leave-management');$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');} }
