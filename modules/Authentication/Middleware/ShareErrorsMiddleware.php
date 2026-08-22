<?php

namespace Modules\Authentication\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

class ShareErrorsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Đảm bảo biến $errors luôn tồn tại trong session
        if (!$request->session()->has('errors')) {
            $request->session()->put('errors', new ViewErrorBag());
        }

        // Share errors với views
        View::share('errors', $request->session()->get('errors', new ViewErrorBag()));

        return $next($request);
    }
}
