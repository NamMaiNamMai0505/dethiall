<?php

namespace Modules\Class\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ClassServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module services here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutes();

        // Load views
        $this->loadViews();

        // Load migrations
        $this->loadMigrations();

        // Publish assets
        $this->publishAssets();
    }

    /**
     * Load module routes
     */
    private function loadRoutes(): void
    {
        Route::group([
            'prefix' => 'classes',
            'namespace' => 'Modules\Class\Controllers',
            'middleware' => ['web', 'auth']
        ], function () {
            require __DIR__ . '/../Routes/web.php';
        });
    }

    /**
     * Load module views
     */
    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', 'class');
    }

    /**
     * Load module migrations
     */
    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Publish module assets
     */
    private function publishAssets(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish views
            $this->publishes([
                __DIR__ . '/../Views' => resource_path('views/modules/class'),
            ], 'class-views');

            // Publish config
            $this->publishes([
                __DIR__ . '/../Config/class.php' => config_path('class.php'),
            ], 'class-config');
        }
    }
}
