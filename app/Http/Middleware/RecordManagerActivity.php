<?php

namespace App\Http\Middleware;

use App\Support\SystemNotifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordManagerActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->user()) {
            return;
        }

        try {
            SystemNotifier::fromManagerRequest($request, $response);
        } catch (\Throwable) {
            // Never block the request lifecycle for notification logging.
        }
    }
}
