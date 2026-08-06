<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function challenge(Request $request)
    {
        $userId = $request->session()->get('2fa_pending_user');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        // Authenticated session path (already logged in, 2FA not yet verified).
        if (Auth::check() && Auth::user()->hasEnabledTwoFactorAuth() && ! $request->session()->get('2fa_verified')) {
            return view('auth.two-factor');
        }

        return view('auth.two-factor', ['user' => $user]);
    }

    public function verify(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (! $user) {
            $userId = $request->session()->get('2fa_pending_user');
            $user = $userId ? User::find($userId) : null;
        }

        if (! $user || ! $user->two_factor_secret) {
            return redirect()->route('login');
        }

        // Try a recovery code first if TOTP fails.
        $verified = $totp->verify($user->two_factor_secret, $validated['code']);

        if (! $verified && $user->two_factor_recovery_codes) {
            $codes = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : json_decode((string) $user->two_factor_recovery_codes, true) ?? [];

            $index = array_search($validated['code'], $codes, true);

            if ($index !== false) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $verified = true;
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $request->session()->put('2fa_verified', true);
        $request->session()->forget('2fa_pending_user');

        if (! Auth::check()) {
            Auth::login($user);
        }

        AuditLogger::record('auth.2fa.verified', $user);

        return redirect()->intended(route('dashboard'));
    }
}
