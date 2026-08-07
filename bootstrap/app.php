<?php

use App\Http\Middleware\EnsureTwoFactorIsVerified;
use App\Http\Middleware\InstallationCompleted;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            '2fa' => EnsureTwoFactorIsVerified::class,
            'installation' => InstallationCompleted::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request) =>
            $request->is('api/*') || $request->wantsJson()
        );
    })
    ->withSchedule(function (Schedule $schedule) {
        // Billing & provisioning automation (requires the Laravel scheduler / cron).
        $schedule->command('kelvcmc:invoices:generate')->dailyAt('00:30')->withoutOverlapping();
        $schedule->command('kelvcmc:invoices:remind')->dailyAt('09:00')->withoutOverlapping();
        $schedule->command('kelvcmc:services:suspend-expired')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('kelvcmc:services:provision-pending')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('kelvcmc:audit:prune')->dailyAt('04:00');
    })
    ->create();
