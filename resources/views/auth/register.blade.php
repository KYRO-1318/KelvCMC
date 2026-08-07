<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create account — {{ kelvcmc_brand() }}</title>
    <link rel="stylesheet" href="{{ asset(config('themes.themes.'.kelvcmc_active_theme().'.css', 'css/themes/kelv.css')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="k-bg-glow relative flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <img src="{{ asset('logo.svg') }}" alt="logo" class="mx-auto h-14 w-14">
                <h1 class="mt-4 text-2xl font-bold text-white">Create your account</h1>
                <p class="mt-1 text-sm text-slate-400">Deploy your services in minutes.</p>
            </div>

            <div class="card">
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Full name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="Jane Doe">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Company (optional)</label>
                        <input type="text" name="company" value="{{ old('company') }}" class="input" placeholder="Acme Inc.">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="input" placeholder="you@company.com">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Password</label>
                            <input type="password" name="password" required class="input" placeholder="Min. 12 characters">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Confirm</label>
                            <input type="password" name="password_confirmation" required class="input" placeholder="Repeat password">
                        </div>
                    </div>
                    <label class="flex items-start gap-2 text-sm text-slate-400">
                        <input type="checkbox" name="terms" required class="mt-0.5 rounded border-slate-700 bg-slate-900 text-violet-500 focus:ring-violet-500">
                        <span>I agree to the <a href="#" class="text-violet-400 hover:underline">Terms of Service</a> and <a href="#" class="text-violet-400 hover:underline">Privacy Policy</a>.</span>
                    </label>
                    <button type="submit" class="btn-primary w-full">Create account</button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-violet-400 hover:text-violet-300">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
