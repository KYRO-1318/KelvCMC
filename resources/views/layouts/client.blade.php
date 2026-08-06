@php
    $theme = config('themes.themes.' . kelvcmc_active_theme(), []);
    $moduleNav = app(\App\Modules\ModuleManager::class)->navItems();
    $currentRoute = request()->route()?->getName();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) && $title ? $title . ' — ' : '' }}{{ kelvcmc_brand() }}</title>

    @if (isset($theme['css']))
        <link rel="stylesheet" href="{{ asset($theme['css']) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <livewire:styles />
    @stack('styles')
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="k-bg-glow relative min-h-screen">
        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-40 border-b bg-slate-950/80 backdrop-blur lg:hidden" style="border-color: var(--k-border)">
            <div class="flex items-center justify-between px-4 py-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('logo.svg') }}" alt="logo" class="h-8 w-8">
                    <span class="text-sm font-bold text-white">{{ kelvcmc_brand() }}</span>
                </a>
                <button type="button" data-mobile-menu class="rounded-lg border border-slate-700/60 p-2 text-slate-300 hover:bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </header>

        <div class="flex">
            {{-- Sidebar --}}
            <aside id="sidebar" class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r bg-slate-950/70 backdrop-blur lg:sticky lg:top-0 lg:flex lg:h-screen" style="border-color: var(--k-border)">
                <div class="flex h-16 items-center gap-2.5 border-b px-5" style="border-color: var(--k-border)">
                    <img src="{{ asset('logo.svg') }}" alt="logo" class="h-9 w-9">
                    <div>
                        <div class="text-sm font-bold leading-tight text-white">{{ kelvcmc_brand() }}</div>
                        <div class="text-[10px] uppercase tracking-widest text-slate-500">{{ \Illuminate\Support\Str::of(config('kelvcmc.brand.tagline'))->limit(26) }}</div>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    <x-client-nav-link :route="'dashboard'" :active="$currentRoute === 'dashboard'" icon="grid" label="Dashboard" />
                    <x-client-nav-link :route="'services.index'" :active="str_starts_with((string) $currentRoute, 'services')" icon="server" label="Services" />
                    <x-client-nav-link :route="'invoices.index'" :active="str_starts_with((string) $currentRoute, 'invoices')" icon="receipt" label="Invoices" />
                    <x-client-nav-link :route="'tickets.index'" :active="str_starts_with((string) $currentRoute, 'tickets')" icon="lifebuoy" label="Support" />
                    <x-client-nav-link :route="'billing.index'" :active="str_starts_with((string) $currentRoute, 'billing')" icon="wallet" label="Billing" />
                    <x-client-nav-link :route="'store.index'" :active="str_starts_with((string) $currentRoute, 'store')" icon="cart" label="Store" />

                    @foreach ($moduleNav as $item)
                        <x-client-nav-link :route="$item['route']" :active="request()->routeIs($item['route'])" :icon="$item['icon'] ?? 'cube'" :label="$item['label']" />
                    @endforeach

                    <div class="pt-4">
                        <div class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-600">Account</div>
                        <x-client-nav-link :route="'profile.index'" :active="str_starts_with((string) $currentRoute, 'profile')" icon="user" label="Profile & 2FA" />
                    </div>
                </nav>

                <div class="border-t p-3" style="border-color: var(--k-border)">
                    @auth
                        <div class="flex items-center gap-3 rounded-lg bg-slate-900/60 p-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white" style="background: linear-gradient(135deg, var(--k-accent), var(--k-accent-strong))">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</div>
                                <div class="truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-slate-500 transition hover:text-rose-400" title="Sign out">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary w-full justify-center">Sign in</a>
                    @endauth
                </div>
            </aside>

            {{-- Main --}}
            <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                @if (session('success'))
                    <div class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
                @endif
                @if (session('info'))
                    <div class="mb-4 flex items-center gap-2 rounded-lg border border-sky-500/30 bg-sky-500/10 px-4 py-3 text-sm text-sky-300">{{ session('info') }}</div>
                @endif
                @if (session('warning'))
                    <div class="mb-4 flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">{{ session('warning') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.querySelector('[data-mobile-menu]')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('hidden');
        });
    </script>
    <livewire:scripts />
    @stack('scripts')
</body>
</html>
