<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ kelvcmc_brand() }} — {{ config('kelvcmc.brand.tagline') }}</title>
    <link rel="stylesheet" href="{{ asset(config('themes.themes.'.kelvcmc_active_theme().'.css', 'css/themes/kelv.css')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="k-bg-glow relative">
        {{-- Nav --}}
        <header class="sticky top-0 z-40 border-b bg-slate-950/70 backdrop-blur" style="border-color: var(--k-border)">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('logo.svg') }}" alt="logo" class="h-9 w-9">
                    <span class="text-sm font-bold text-white">{{ kelvcmc_brand() }}</span>
                </div>
                <nav class="flex items-center gap-3">
                    <a href="{{ route('store.index') }}" class="hidden text-sm font-medium text-slate-400 transition hover:text-white sm:block">Store</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary !px-4 !py-2">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary !px-4 !py-2">Sign in</a>
                        <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2">Get started</a>
                    @endauth
                </nav>
            </div>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-4 pb-20 pt-16 text-center sm:pt-24">
            <span class="badge">Open source cloud management platform</span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-6xl">
                Manage your hosting business <span style="background: linear-gradient(90deg, var(--k-accent), #22d3ee); -webkit-background-clip: text; background-clip: text; color: transparent;">on your own terms</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base text-slate-400 sm:text-lg">
                {{ kelvcmc_brand() }} is a free, open source billing and cloud management platform for hosting companies —
                a modern alternative to WHMCS, Blesta and Paymenter. Self-hosted, Laravel-powered, Plesk-ready.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-primary !px-6 !py-3 !text-base">Start free</a>
                <a href="{{ route('store.index') }}" class="btn-secondary !px-6 !py-3 !text-base">Browse services</a>
            </div>
            <div class="mt-10 grid grid-cols-3 gap-4 text-center">
                @foreach ([['100%', 'Open source (MIT)'], ['1 min', 'Deployment on Plesk'], ['24/7', 'Automated provisioning']] as $stat)
                    <div>
                        <div class="text-2xl font-extrabold text-white">{{ $stat[0] }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $stat[1] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Features --}}
        <section class="mx-auto max-w-6xl px-4 py-12">
            <h2 class="text-center text-2xl font-bold text-white">Everything a hosting company needs</h2>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Billing & invoicing', 'Automatic renewals, VAT, coupons and PDF invoices.'],
                    ['Automated provisioning', 'Plesk, Pterodactyl and Proxmox integration out of the box.'],
                    ['Payment gateways', 'Stripe, PayPal, Mollie, Coinbase, bank transfer and internal credit.'],
                    ['Client portal', 'Dark, responsive SaaS-style dashboard for your customers.'],
                    ['Support tickets', 'Categories, priorities, internal notes and attachments.'],
                    ['Open API', 'REST API with Sanctum auth and OpenAPI documentation.'],
                ] as $feature)
                    <div class="card hover:-translate-y-0.5 transition">
                        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <h3 class="mt-3 font-semibold text-white">{{ $feature[0] }}</h3>
                        <p class="mt-1 text-sm text-slate-400">{{ $feature[1] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Featured products --}}
        @if ($featured->count())
            <section class="mx-auto max-w-6xl px-4 py-12">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Popular services</h2>
                    <a href="{{ route('store.index') }}" class="text-sm font-medium text-violet-400 hover:text-violet-300">View all →</a>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $product)
                        <a href="{{ route('store.show', $product) }}" class="card transition hover:-translate-y-0.5 hover:border-violet-500/40">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-white">{{ $product->name }}</h3>
                                <span class="badge">{{ str_replace('_', ' ', $product->type) }}</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-400">{{ $product->description }}</p>
                            <div class="mt-4 flex items-end justify-between border-t border-slate-800/80 pt-3">
                                <div>
                                    <span class="text-2xl font-bold text-white">{{ kelvcmc_money($product->price_monthly) }}</span>
                                    <span class="text-xs text-slate-500">/mo</span>
                                </div>
                                <span class="text-xs font-semibold text-violet-400">Order now →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Footer --}}
        <footer class="border-t py-10" style="border-color: var(--k-border)">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('logo.svg') }}" alt="logo" class="h-7 w-7">
                    <span class="text-sm font-bold text-white">{{ kelvcmc_brand() }}</span>
                </div>
                <p class="text-xs text-slate-500">© {{ date('Y') }} {{ kelvcmc_brand() }} · MIT License · Built with Laravel & Filament</p>
            </div>
        </footer>
    </div>
</body>
</html>
