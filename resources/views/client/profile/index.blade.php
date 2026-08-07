<x-client-layout title="Profile">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Profile & security</h1>
            <p class="mt-1 text-sm text-slate-400">Manage your personal details and two-factor authentication.</p>
        </div>

        <div class="card">
            <h2 class="font-semibold text-white">Personal information</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Full name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Company</label>
                    <input type="text" name="company" value="{{ old('company', $user->company) }}" class="input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                    <input type="email" value="{{ $user->email }}" disabled class="input opacity-60">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Address</label>
                    <input type="text" name="address" value="{{ old('address', $user->address) }}" class="input">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">City</label>
                    <input type="text" name="city" value="{{ old('city', $user->city) }}" class="input">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">ZIP</label>
                        <input type="text" name="zip" value="{{ old('zip', $user->zip) }}" class="input">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Country</label>
                        <input type="text" name="country" value="{{ old('country', $user->country) }}" maxlength="2" placeholder="FR" class="input uppercase">
                    </div>
                </div>
                <button type="submit" class="btn-primary sm:col-span-2">Save changes</button>
            </form>
        </div>

        <div class="card">
            <h2 class="font-semibold text-white">Change password</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Current password</label>
                    <input type="password" name="current_password" class="input" autocomplete="current-password">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">New password</label>
                    <input type="password" name="password" class="input" autocomplete="new-password">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Confirm</label>
                    <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-secondary sm:col-span-3">Update password</button>
            </form>
        </div>

        {{-- Two-factor --}}
        <div class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-white">Two-factor authentication</h2>
                @if ($user->hasEnabledTwoFactorAuth())
                    <span class="badge !bg-emerald-500/15 !text-emerald-300">Enabled</span>
                @elseif ($user->hasPendingTwoFactorSetup())
                    <span class="badge !bg-amber-500/15 !text-amber-300">Pending confirmation</span>
                @else
                    <span class="badge">Disabled</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-400">Add an extra layer of security to your account with an authenticator app.</p>

            @if (! $user->hasEnabledTwoFactorAuth() && ! $user->hasPendingTwoFactorSetup())
                <form method="POST" action="{{ route('profile.2fa.setup') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn-primary">Set up 2FA</button>
                </form>
            @endif

            @if ($user->hasPendingTwoFactorSetup())
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">1 · Add the secret to your app</div>
                        <div class="mt-3 rounded-lg border border-slate-700 bg-slate-950 p-4 text-center">
                            <code class="break-all text-sm tracking-[0.18em] text-violet-300">{{ $twoFactorSecret }}</code>
                        </div>
                        <p class="mt-3 text-center text-xs text-slate-500">Enter this secret manually in your authenticator app. It is shown only while setup is pending.</p>
                    </div>
                    <div class="flex flex-col">
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">2 · Confirm with a code</div>
                        <form method="POST" action="{{ route('profile.2fa.confirm') }}" class="mt-3 space-y-3">
                            @csrf
                            <input type="text" name="code" inputmode="numeric" maxlength="6" required class="input text-center text-xl font-mono tracking-[0.4em]" placeholder="••••••">
                            <button type="submit" class="btn-primary w-full">Confirm & enable</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($user->hasEnabledTwoFactorAuth())
                <div class="mt-5 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Recovery codes</div>
                    <p class="mt-1 text-xs text-slate-500">Store these somewhere safe. Each code can be used once if you lose your device.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-slate-300 sm:grid-cols-4">
                        @foreach ($recoveryCodes ?? [] as $code)
                            <code class="rounded bg-slate-800/70 px-2 py-1.5 text-center">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('profile.2fa.disable') }}" class="mt-4 flex items-end gap-3">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" maxlength="6" required class="input !w-40 text-center font-mono" placeholder="Current code">
                    <button type="submit" class="btn-danger" onsubmit="return confirm('Disable two-factor authentication?')">Disable 2FA</button>
                </form>
            @endif
        </div>
    </div>
</x-client-layout>
