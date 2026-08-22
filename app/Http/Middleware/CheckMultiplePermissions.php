<?php

namespace App\Http\Middleware;

use App\Support\PermissionCheck;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMultiplePermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions, string $type = 'all'): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để truy cập trang này.');
        }

        $permissionArray = array_values(array_filter(explode('|', $permissions)));

        if ($type === 'any') {
            if (! PermissionCheck::userCanAny($permissionArray)) {
                abort(403, 'Bạn không có quyền truy cập trang này.');
            }
        } else {
            foreach ($permissionArray as $permission) {
                if (! PermissionCheck::userCan($permission)) {
                    abort(403, 'Bạn không có quyền truy cập trang này.');
                }
            }
        }

        return $next($request);
    }
}
