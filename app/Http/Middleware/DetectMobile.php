<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Jenssegers\Agent\Agent;

class DetectMobile
{
    public function handle(Request $request, Closure $next)
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent() ?? '');

        // Treat phones and tablets as mobile (includes iPadOS 13+ which spoofs a Mac UA)
        $isMobile = $agent->isMobile() || $agent->isTablet();

        View::share('isMobile', $isMobile);
        View::share('layout', $isMobile ? 'layouts.app_mobile' : 'layouts.app');

        return $next($request);
    }
}
