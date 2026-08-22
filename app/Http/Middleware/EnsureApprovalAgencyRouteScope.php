<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApprovalAgency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovalAgencyRouteScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (str_starts_with((string) $routeName, 'standard-hours.')) {
            abort_unless(
                ApprovalAgency::canAccessStandardHoursRoute($user, $routeName),
                403,
                ApprovalAgency::isResearchAgency($user)
                    ? 'Cơ quan của bạn chỉ được truy cập nghiệp vụ phê duyệt NCKH.'
                    : 'Cơ quan của bạn không được truy cập nghiệp vụ NCKH.'
            );
        }

        if (str_starts_with((string) $routeName, 'grades.')) {
            abort_unless(
                ApprovalAgency::canAccessGradesRoute($user),
                403,
                'Ban Khoa học Quân sự không được truy cập Quản lý điểm.'
            );
        }

        return $next($request);
    }
}
