<?php

namespace App\Providers;

use App\Support\TotpService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TotpService::class);

        // DnsProvider is bound in IntegrationServiceProvider
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });

        RateLimiter::for('2fa', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
