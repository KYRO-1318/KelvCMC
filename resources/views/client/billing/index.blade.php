<x-client-layout title="Billing">
    @php
        $targetInvoice = request()->query('invoice')
            ? $user->openInvoices()->find(request()->query('invoice'))
            : $user->openInvoices()->latest()->first();
    @endphp
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Billing</h1>
            <p class="mt-1 text-sm text-slate-400">Manage your payments and account credit.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Open invoices --}}
                <div class="card">
                    <h2 class="font-semibold text-white">Open invoices</h2>
                    <div class="mt-3 space-y-3">
                        @forelse ($openInvoices as $invoice)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-3">
                                <div>
                                    <div class="text-sm font-medium text-slate-200">{{ $invoice->number }}</div>
                                    <div class="text-xs text-slate-500">Due {{ $invoice->due_at?->format('M j, Y') }}</div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-white">{{ kelvcmc_money($invoice->total) }}</span>
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">Details</a>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-slate-500">No open invoices. 🎉</p>
                        @endforelse
                    </div>
                </div>

                {{-- Pay invoice --}}
                @if ($targetInvoice && $targetInvoice->isPayable())
                    <div class="card" style="border-color: color-mix(in srgb, var(--k-accent) 35%, transparent)">
                        <h2 class="font-semibold text-white">Pay {{ $targetInvoice->number }}</h2>
                        <p class="mt-1 text-sm text-slate-400">Total due: <span class="font-semibold text-white">{{ kelvcmc_money($targetInvoice->total) }}</span></p>

                        <form method="POST" action="{{ route('billing.pay', $targetInvoice) }}" class="mt-4 space-y-3">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($gateways as $option)
                                    @php $gateway = $option['gateway']; @endphp
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-700/60 bg-slate-900/50 px-4 py-3 transition hover:border-violet-500/40 has-[:checked]:border-violet-500/60 has-[:checked]:bg-violet-500/10">
                                        <input type="radio" name="gateway" value="{{ $gateway->id() }}" class="h-4 w-4 accent-violet-500" @checked($loop->first)>
                                        <span class="text-sm font-medium text-slate-200">{{ $gateway->name() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn-primary">Continue to payment</button>
                        </form>
                    </div>
                @endif

                {{-- Recent payments --}}
                <div class="card">
                    <h2 class="font-semibold text-white">Recent payments</h2>
                    <div class="mt-3 space-y-2">
                        @forelse ($recentPayments as $payment)
                            <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-2.5 text-sm">
                                <div class="flex items-center gap-3">
                                    <span class="badge !bg-slate-700/40 !text-slate-300">{{ $payment->gateway }}</span>
                                    <span class="text-xs text-slate-500">{{ $payment->paid_at?->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="badge">{{ $payment->status }}</span>
                                    <span class="font-semibold text-white">{{ kelvcmc_money($payment->amount) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-slate-500">No payments yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Credit sidebar --}}
            <div class="space-y-6">
                <div class="card text-center">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Account credit</div>
                    <div class="mt-2 text-3xl font-bold text-white">{{ kelvcmc_money($user->credit_balance) }}</div>
                    <p class="mt-2 text-xs text-slate-500">Credit is automatically applied when paying invoices.</p>
                </div>

                <div class="card">
                    <h2 class="font-semibold text-white">Payment methods</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($gateways as $option)
                            @foreach ($option['methods'] as $method)
                                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2 text-sm">
                                    <span class="text-slate-200">{{ $method->label }}</span>
                                    <span class="badge">{{ $method->type }}</span>
                                </div>
                            @endforeach
                        @endforeach
                        <p class="pt-1 text-xs text-slate-500">Saved cards are added automatically when paying with a card gateway.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
