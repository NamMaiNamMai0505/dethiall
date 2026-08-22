<?php

namespace Modules\Specialization\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SpecializationServiceProvider extends ServiceProvider
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
            'prefix' => 'specializations',
            'namespace' => 'Modules\Specialization\Controllers',
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
        $this->loadViewsFrom(__DIR__ . '/../Views', 'specialization');
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
                __DIR__ . '/../Views' => resource_path('views/modules/specialization'),
            ], 'specialization-views');

            // Publish config
            $this->publishes([
                __DIR__ . '/../Config/specialization.php' => config_path('specialization.php'),
            ], 'specialization-config');
        }
    }
}
