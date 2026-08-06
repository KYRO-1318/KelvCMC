<x-client-layout title="Store">
    <div class="space-y-10">
        <div class="flex items-end justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Store</h1>
                <p class="mt-1 text-sm text-slate-400">Choose a service and deploy in minutes.</p>
            </div>
        </div>

        @foreach ($catalog as $type => $products)
            <section>
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500">{{ str_replace('_', ' ', $type) }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        <a href="{{ route('store.show', $product) }}" class="card group transition hover:-translate-y-0.5 hover:border-violet-500/40">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-white group-hover:text-violet-300">{{ $product->name }}</h3>
                                <span class="badge">{{ $product->module }}</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-slate-400">{{ $product->description }}</p>
                            @if ($product->features)
                                <ul class="mt-3 space-y-1">
                                    @foreach (collect($product->features)->take(3) as $key => $value)
                                        <li class="flex items-center gap-2 text-xs text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            {{ $key }}: <span class="text-slate-300">{{ $value }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="mt-4 flex items-end justify-between border-t border-slate-800/80 pt-3">
                                <div>
                                    <span class="text-2xl font-bold text-white">{{ kelvcmc_money($product->price_monthly) }}</span>
                                    <span class="text-xs text-slate-500">/mo</span>
                                </div>
                                <span class="text-xs font-semibold text-violet-400">Order →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-client-layout>
