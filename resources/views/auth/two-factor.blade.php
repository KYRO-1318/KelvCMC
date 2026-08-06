<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-factor authentication — {{ kelvcmc_brand() }}</title>
    <link rel="stylesheet" href="{{ asset(config('themes.themes.'.kelvcmc_active_theme().'.css', 'css/themes/kelv.css')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="k-bg-glow relative flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <img src="{{ asset('logo.svg') }}" alt="logo" class="mx-auto h-14 w-14">
                <h1 class="mt-4 text-2xl font-bold text-white">Two-factor authentication</h1>
                <p class="mt-1 text-sm text-slate-400">Enter the 6-digit code from your authenticator app.</p>
            </div>

            <div class="card">
                <form method="POST" action="{{ route('2fa.verify') }}" class="mt-2 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Authentication code</label>
                        <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus class="input text-center text-2xl tracking-[0.5em] font-mono" placeholder="••••••">
                    </div>
                    <button type="submit" class="btn-primary w-full">Verify</button>
                </form>

                <p class="mt-5 text-center text-xs text-slate-500">
                    Lost your device? Use a recovery code from your setup email.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
