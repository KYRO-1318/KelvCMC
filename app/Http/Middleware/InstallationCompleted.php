<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallationCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(InstallationService::class)->isInstalled()) {
            return redirect()->route('landing')->with('info', 'KelvCMC is already installed.');
        }

        return $next($request);
    }
}
