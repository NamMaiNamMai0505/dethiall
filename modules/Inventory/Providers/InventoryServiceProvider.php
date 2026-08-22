<?php
namespace Modules\Inventory\Providers;
use Illuminate\Support\ServiceProvider;
class InventoryServiceProvider extends ServiceProvider { public function boot():void{$this->loadRoutesFrom(__DIR__.'/../Routes/web.php');$this->loadViewsFrom(__DIR__.'/../Views','inventory');$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');} }
