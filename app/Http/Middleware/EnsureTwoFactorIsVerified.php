<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admins with pending 2FA setup are forced through the flow.
        $force = config('kelvcmc.security.force_2fa_for_admins', false);

        if ($user->hasEnabledTwoFactorAuth() && ! $request->session()->get('2fa_verified')) {
            return redirect()->route('2fa.challenge');
        }

        if ($force && $user->isAdmin() && ! $user->hasEnabledTwoFactorAuth() && ! $request->routeIs('profile.*', 'filament.*', 'livewire.*')) {
            // Admins without 2FA are redirected to the client profile page
            // where they can enable it. Filament/Livewire routes are excluded
            // to avoid redirect loops inside the admin panel.
            $redirectTo = route('profile.index', absolute: false);
            if ($redirectTo !== $request->path()) {
                return redirect()->route('profile.index')->with('warning', 'Enable two-factor authentication to continue.');
            }
        }

        return $next($request);
    }
}
