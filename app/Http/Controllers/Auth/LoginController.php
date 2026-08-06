<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (RateLimiter::tooManyAttempts('login:'.$request->input('email').'|'.$request->ip(), 5)) {
            $seconds = RateLimiter::availableIn('login:'.$request->input('email').'|'.$request->ip());

            return back()->withErrors(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit('login:'.$request->input('email').'|'.$request->ip());

            return back()->withErrors(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear('login:'.$request->input('email').'|'.$request->ip());

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditLogger::record('auth.login', $user);

        if ($user->hasEnabledTwoFactorAuth()) {
            $request->session()->put('2fa_pending_user', $user->id);
            Auth::logout();

            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();
        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLogger::record('auth.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
