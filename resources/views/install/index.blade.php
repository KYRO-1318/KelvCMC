@php
    $steps = [1 => 'Server', 2 => 'Database', 3 => 'APP_KEY', 4 => 'Migration', 5 => 'Administrator', 6 => 'Complete'];
    $data = $data ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install KelvCMC</title>
    <link rel="stylesheet" href="{{ asset('css/themes/kelv.css') }}">
    @if (is_file(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
<style>
body{background:#020617;color:#f8fafc}.card{border:1px solid rgba(148,163,184,.14);border-radius:1rem;background:rgba(15,23,42,.86);padding:1.5rem;box-shadow:0 20px 50px rgba(0,0,0,.25)}.input{width:100%;border:1px solid #334155;border-radius:.5rem;background:#0f172a;color:#f8fafc;padding:.65rem .8rem}.btn-primary{display:inline-flex;justify-content:center;border:0;border-radius:.5rem;background:#7c3aed;color:#fff;padding:.7rem 1rem;font-weight:600;cursor:pointer}.btn-primary:hover{background:#8b5cf6}
</style>
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-10">
        <div class="grid w-full gap-8 lg:grid-cols-[220px_1fr]">
            <aside class="space-y-5">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo.svg') }}" alt="KelvCMC" class="h-11 w-11">
                    <div><div class="font-bold text-white">KelvCMC</div><div class="text-xs text-slate-500">Setup wizard</div></div>
                </div>
                <nav class="grid grid-cols-3 gap-2 lg:block lg:space-y-2">
                    @foreach ($steps as $number => $label)
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2 text-xs {{ $step === $number ? 'bg-violet-500/15 text-violet-300' : ($step > $number ? 'text-emerald-400' : 'text-slate-500') }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full border border-current text-xs font-bold">{{ $step > $number ? '✓' : $number }}</span>
                            <span class="hidden sm:inline">{{ $label }}</span>
                        </div>
                    @endforeach
                </nav>
            </aside>

            <section class="card min-h-[520px]">
                <div class="mb-8 border-b border-slate-800 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-400">Step {{ $step }} of 6</p>
                    <h1 class="mt-2 text-2xl font-bold text-white">{{ $steps[$step] }}</h1>
                    <p class="mt-1 text-sm text-slate-400">Configure your KelvCMC installation safely.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-300"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                @if ($step === 1)
                    <div class="space-y-3">
                        @foreach ($requirements as $name => $passed)
                            <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3 text-sm"><span class="text-slate-300">{{ $name }}</span><span class="font-semibold {{ $passed ? 'text-emerald-400' : 'text-rose-400' }}">{{ $passed ? 'Ready' : 'Missing' }}</span></div>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('install.requirements') }}" class="mt-8">@csrf<button class="btn-primary w-full sm:w-auto">Continue</button></form>
                @elseif ($step === 2)
                    <form method="POST" action="{{ route('install.database') }}" class="grid gap-4 sm:grid-cols-2">@csrf
                        <label class="text-sm text-slate-300">Driver<select name="db_connection" class="input mt-2"><option value="mysql" @selected(old('db_connection', $data['db_connection'] ?? 'mysql') === 'mysql')>MySQL</option><option value="mariadb" @selected(old('db_connection', $data['db_connection'] ?? '') === 'mariadb')>MariaDB</option></select></label>
                        <label class="text-sm text-slate-300">Host<input name="db_host" value="{{ old('db_host', $data['db_host'] ?? '127.0.0.1') }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Port<input type="number" name="db_port" value="{{ old('db_port', $data['db_port'] ?? 3306) }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Database<input name="db_database" value="{{ old('db_database', $data['db_database'] ?? '') }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Username<input name="db_username" value="{{ old('db_username', $data['db_username'] ?? '') }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Password<input type="password" name="db_password" class="input mt-2"></label>
                        <button class="btn-primary sm:col-span-2">Test connection & continue</button>
                    </form>
                @elseif ($step === 3)
                    <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-5"><h2 class="font-semibold text-emerald-300">Environment ready</h2><p class="mt-2 text-sm text-slate-300">Generate a secure APP_KEY before creating the database schema.</p></div>
                    <form method="POST" action="{{ route('install.key') }}" class="mt-8">@csrf<button class="btn-primary">Generate APP_KEY</button></form>
                @elseif ($step === 4)
                    <div class="rounded-xl border border-violet-500/20 bg-violet-500/10 p-5"><h2 class="font-semibold text-violet-300">Ready to migrate</h2><p class="mt-2 text-sm text-slate-300">KelvCMC will create all billing, hosting, support and permissions tables.</p></div>
                    <form method="POST" action="{{ route('install.migrate') }}" class="mt-8">@csrf<button class="btn-primary">Run migrations</button></form>
                @elseif ($step === 5)
                    <form method="POST" action="{{ route('install.finish') }}" class="grid gap-4 sm:grid-cols-2">@csrf
                        <label class="text-sm text-slate-300">Site name<input name="site_name" value="{{ old('site_name', config('kelvcmc.brand.name', 'KelvCMC')) }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Site URL<input type="url" name="site_url" value="{{ old('site_url', config('app.url')) }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Currency<input name="currency" value="{{ old('currency', 'EUR') }}" maxlength="3" class="input mt-2 uppercase" required></label>
                        <label class="text-sm text-slate-300">Language<input name="locale" value="{{ old('locale', config('app.locale', 'en')) }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Admin name<input name="admin_name" value="{{ old('admin_name') }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Admin email<input type="email" name="admin_email" value="{{ old('admin_email') }}" class="input mt-2" required></label>
                        <label class="text-sm text-slate-300">Password<input type="password" name="admin_password" class="input mt-2" minlength="12" required></label>
                        <label class="text-sm text-slate-300">Confirm password<input type="password" name="admin_password_confirmation" class="input mt-2" minlength="12" required></label>
                        <button class="btn-primary sm:col-span-2">Install KelvCMC</button>
                    </form>
                @else
                    <div class="py-12 text-center"><div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-3xl text-emerald-400">✓</div><h2 class="mt-5 text-2xl font-bold text-white">Installation complete</h2><p class="mt-2 text-slate-400">Your portal is ready.</p><a href="{{ route('login') }}" class="btn-primary mt-8">Open login</a></div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
