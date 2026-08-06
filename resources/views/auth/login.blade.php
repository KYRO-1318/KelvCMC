<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — {{ kelvcmc_brand() }}</title>
    <link rel="stylesheet" href="{{ asset(config('themes.themes.'.kelvcmc_active_theme().'.css', 'css/themes/kelv.css')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="k-bg-glow relative flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <img src="{{ asset('logo.svg') }}" alt="logo" class="mx-auto h-14 w-14">
                <h1 class="mt-4 text-2xl font-bold text-white">{{ kelvcmc_brand() }}</h1>
                <p class="mt-1 text-sm text-slate-400">{{ config('kelvcmc.brand.tagline') }}</p>
            </div>

            <div class="card">
                <h2 class="text-lg font-semibold text-white">Welcome back</h2>
                <p class="mt-1 text-sm text-slate-400">Sign in to your account to continue.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus class="input" placeholder="you@company.com">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Password</label>
                        <input type="password" name="password" required class="input" placeholder="••••••••">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-400">
                        <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-900 text-violet-500 focus:ring-violet-500">
                        Remember me
                    </label>
                    <button type="submit" class="btn-primary w-full">Sign in</button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-400">
                    New to {{ kelvcmc_brand() }}?
                    <a href="{{ route('register') }}" class="font-semibold text-violet-400 hover:text-violet-300">Create an account</a>
                </p>
            </div>

            <p class="mt-6 text-center text-xs text-slate-600">
                <a href="{{ route('landing') }}" class="hover:text-slate-400">← Back to homepage</a>
            </p>
        </div>
    </div>
</body>
</html>
