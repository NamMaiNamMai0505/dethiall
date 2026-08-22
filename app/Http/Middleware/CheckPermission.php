<?php

namespace App\Http\Middleware;

use App\Support\PermissionCheck;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để truy cập trang này.');
        }

        // A pipe-separated list is an OR expression, matching the syntax
        // used by the permission middleware declarations throughout the app.
        $permissions = array_filter(array_map('trim', explode('|', $permission)));

        if (! PermissionCheck::userCanAny($permissions)) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
