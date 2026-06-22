<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAuth
{
    public const SESSION_KEY = 'dashboard_authed';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get(self::SESSION_KEY)) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
