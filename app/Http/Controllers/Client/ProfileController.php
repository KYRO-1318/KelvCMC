<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('client.profile.index', [
            'user' => $user,
            'twoFactorSecret' => $user->hasPendingTwoFactorSetup() ? $user->two_factor_secret : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuth() ? $user->two_factor_recovery_codes : null,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $hasPasswordChange = $request->filled('current_password')
            || $request->filled('password')
            || $request->filled('password_confirmation');

        if ($hasPasswordChange) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ]);

            $user->update(['password' => Hash::make($request->input('password'))]);
            AuditLogger::record('profile.password_updated', $user);
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'company' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'zip' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'size:2'],
            ]);

            $user->update($validated);
            AuditLogger::record('profile.updated', $user);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function setup2fa(TotpService $totp)
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuth()) {
            return back()->with('info', '2FA is already enabled.');
        }

        $user->forceFill([
            'two_factor_secret' => $totp->generateSecret(),
            'two_factor_recovery_codes' => collect(range(1, 8))->map(fn () => strtoupper(implode('-', str_split(bin2hex(random_bytes(4)), 4))))->all(),
        ])->save();

        return back();
    }

    public function confirm2fa(Request $request, TotpService $totp)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $user->two_factor_secret) {
            return back()->withErrors(['code' => 'No pending 2FA setup.']);
        }

        if (! $totp->verify($user->two_factor_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->put('2fa_verified', true);

        AuditLogger::record('auth.2fa.enabled', $user);

        return back()->with('success', 'Two-factor authentication enabled.');
    }

    public function disable2fa(Request $request, TotpService $totp)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $user->two_factor_secret || ! $totp->verify($user->two_factor_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $user->flushTwoFactor();

        AuditLogger::record('auth.2fa.disabled', $user);

        return back()->with('success', 'Two-factor authentication disabled.');
    }
}
