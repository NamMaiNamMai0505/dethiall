<?php

namespace Modules\Trash\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TrashServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'trash');

        Route::middleware(['web', 'auth'])
            ->prefix('trash')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
