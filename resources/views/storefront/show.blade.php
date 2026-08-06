<x-client-layout :title="$product->name">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.index') }}" class="text-slate-500 transition hover:text-white">←</a>
            <h1 class="text-2xl font-bold text-white">{{ $product->name }}</h1>
            <span class="badge">{{ $product->type }}</span>
        </div>

        <p class="text-slate-400">{{ $product->description }}</p>

        @if ($product->features)
            <div class="card">
                <h2 class="font-semibold text-white">Features</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($product->features as $key => $value)
                        <li class="flex items-center gap-2 text-sm text-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            {{ $key }}: <span class="text-slate-400">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @auth
            <form method="POST" action="{{ route('store.order', $product) }}" class="card space-y-5">
                @csrf

                @if ($product->plans->count())
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Select a plan</label>
                        <div class="grid gap-3 sm:grid-cols-{{ min(3, $product->plans->count()) }}">
                            @foreach ($product->plans as $plan)
                                <label class="cursor-pointer rounded-lg border border-slate-700/60 bg-slate-900/50 p-4 transition has-[:checked]:border-violet-500/60 has-[:checked]:bg-violet-500/10">
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" class="hidden" @checked($loop->first)>
                                    <div class="font-semibold text-white">{{ $plan->name }}</div>
                                    <div class="mt-1 text-lg font-bold" style="color: var(--k-accent)">{{ kelvcmc_money($plan->price_monthly) }}<span class="text-xs font-normal text-slate-500">/mo</span></div>
                                    @if ($plan->specs())
                                        <ul class="mt-2 space-y-0.5 text-xs text-slate-400">
                                            @foreach ($plan->specs() as $label => $value)
                                                <li>{{ $label }}: <span class="text-slate-300">{{ $value }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Billing cycle</label>
                        <select name="cycle" class="input">
                            @if ($product->is_recurring)
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semi_annually">Semi-annually</option>
                                <option value="annually">Annually</option>
                            @else
                                <option value="onetime">One time</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Coupon (optional)</label>
                        <input type="text" name="coupon" class="input uppercase" placeholder="WELCOME20">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Domain / hostname (optional)</label>
                    <input type="text" name="config[domain]" class="input" placeholder="example.com">
                </div>

                <button type="submit" class="btn-primary w-full">Place order</button>
            </form>
        @else
            <div class="card text-center py-10">
                <p class="text-slate-400">You need an account to order this service.</p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn-primary">Create an account</a>
                    <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                </div>
            </div>
        @endauth
    </div>
</x-client-layout>
