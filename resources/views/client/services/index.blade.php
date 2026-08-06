<x-client-layout title="Services">
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Your services</h1>
                <p class="mt-1 text-sm text-slate-400">All products and services attached to your account.</p>
            </div>
            <a href="{{ route('store.index') }}" class="btn-primary">Order new service</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($services as $service)
                <a href="{{ route('services.show', $service) }}" class="card group transition hover:-translate-y-0.5 hover:border-violet-500/40">
                    <div class="flex items-start justify-between">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg text-lg font-bold text-white" style="background: linear-gradient(135deg, var(--k-accent), var(--k-accent-strong))">
                            {{ strtoupper(substr($service->name, 0, 1)) }}
                        </div>
                        <span class="badge {{ $service->status === 'active' ? '!bg-emerald-500/15 !text-emerald-300' : ($service->status === 'suspended' ? '!bg-amber-500/15 !text-amber-300' : '') }}">{{ ucfirst($service->status) }}</span>
                    </div>
                    <h3 class="mt-3 font-semibold text-white group-hover:text-violet-300">{{ $service->name }}</h3>
                    <p class="mt-0.5 text-sm text-slate-400">{{ $service->domain ?? ($service->plan?->name ?? '—') }}</p>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3 text-sm">
                        <span class="text-slate-500">Renews {{ $service->expires_at?->format('M j, Y') ?? '—' }}</span>
                        <span class="font-semibold text-white">{{ kelvcmc_money($service->price) }}</span>
                    </div>
                </a>
            @empty
                <div class="card col-span-full py-12 text-center">
                    <p class="text-slate-400">You don't have any services yet.</p>
                    <a href="{{ route('store.index') }}" class="btn-primary mt-4">Browse the store</a>
                </div>
            @endforelse
        </div>

        {{ $services->links() }}
    </div>
</x-client-layout>
