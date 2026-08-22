<?php

namespace Modules\Authentication\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Routing\Router;
use Modules\Authentication\Middleware\ShareErrorsMiddleware;

class AuthenticationServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Authentication';
    protected string $moduleNameLower = 'authentication';

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMiddleware();
        $this->shareGlobalVariables();
    }

    public function register(): void
    {
        //
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', $this->moduleNameLower);
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app[Router::class];
        $router->aliasMiddleware('auth.errors', ShareErrorsMiddleware::class);
    }

    protected function shareGlobalVariables(): void
    {
        // Share errors với tất cả views authentication
        View::composer('authentication::*', function ($view) {
            $errors = session()->get('errors', new ViewErrorBag());
            $view->with('errors', $errors);
        });

        // Share các biến global khác nếu cần
        View::share('app_name', config('app.name', 'Laravel'));
    }
}
