<x-client-layout :title="$invoice->number">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('invoices.index') }}" class="text-slate-500 transition hover:text-white">←</a>
                <div>
                    <h1 class="text-2xl font-bold text-white">Invoice {{ $invoice->number }}</h1>
                    <p class="text-sm text-slate-400">Issued {{ $invoice->created_at->format('M j, Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge {{ $invoice->status === 'paid' ? '!bg-emerald-500/15 !text-emerald-300' : ($invoice->status === 'overdue' ? '!bg-rose-500/15 !text-rose-300' : '') }}">{{ ucfirst($invoice->status) }}</span>
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Download PDF
                </a>
            </div>
        </div>

        @if ($invoice->isPayable())
            <div class="card flex flex-wrap items-center justify-between gap-4" style="border-color: color-mix(in srgb, var(--k-accent) 35%, transparent)">
                <div>
                    <div class="text-sm text-slate-400">Amount due</div>
                    <div class="text-2xl font-bold text-white">{{ kelvcmc_money($invoice->total) }}</div>
                    <div class="text-xs text-slate-500">Due {{ $invoice->due_at?->format('M j, Y') }}</div>
                </div>
                <a href="{{ route('billing.index') }}?invoice={{ $invoice->id }}" class="btn-primary">Pay this invoice</a>
            </div>
        @endif

        <div class="card">
            <div class="flex flex-wrap justify-between gap-4 border-b border-slate-800 pb-5">
                <div>
                    <div class="text-xs uppercase tracking-widest text-slate-500">From</div>
                    <div class="mt-1 font-semibold text-white">{{ kelvcmc_brand() }}</div>
                    <div class="text-sm text-slate-400">{{ config('kelvcmc.brand.tagline') }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-widest text-slate-500">Billed to</div>
                    <div class="mt-1 font-semibold text-white">{{ $invoice->user->name }}</div>
                    <div class="text-sm text-slate-400">{{ $invoice->user->company }}</div>
                    <div class="text-sm text-slate-400">{{ $invoice->user->email }}</div>
                    @if ($invoice->user->country)
                        <div class="text-sm text-slate-400">{{ $invoice->user->country }}</div>
                    @endif
                </div>
            </div>

            <table class="mt-4 w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Description</th>
                        <th class="py-2 pr-4 text-right font-medium">Qty</th>
                        <th class="py-2 pr-4 text-right font-medium">Unit price</th>
                        <th class="py-2 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-t border-slate-800/70">
                            <td class="py-3 pr-4 text-slate-200">{{ $item->description }}</td>
                            <td class="py-3 pr-4 text-right text-slate-400">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td class="py-3 pr-4 text-right text-slate-400">{{ kelvcmc_money($item->unit_price) }}</td>
                            <td class="py-3 text-right font-medium text-white">{{ kelvcmc_money($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 ml-auto w-full max-w-xs space-y-1.5 border-t border-slate-800 pt-4 text-sm">
                <div class="flex justify-between text-slate-400"><span>Subtotal</span><span>{{ kelvcmc_money($invoice->subtotal) }}</span></div>
                @if ($invoice->discount > 0)
                    <div class="flex justify-between text-emerald-400"><span>Discount</span><span>− {{ kelvcmc_money($invoice->discount) }}</span></div>
                @endif
                <div class="flex justify-between text-slate-400"><span>VAT ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }}%)</span><span>{{ kelvcmc_money($invoice->tax_amount) }}</span></div>
                <div class="flex justify-between pt-2 text-base font-bold text-white"><span>Total</span><span>{{ kelvcmc_money($invoice->total) }}</span></div>
            </div>
        </div>

        @if ($invoice->payments->count())
            <div class="card">
                <h2 class="font-semibold text-white">Payments</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($invoice->payments as $payment)
                        <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-4 py-2.5 text-sm">
                            <div>
                                <span class="font-medium text-slate-200">{{ $payment->gateway }}</span>
                                <span class="ml-2 text-xs text-slate-500">{{ $payment->reference }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="badge">{{ $payment->status }}</span>
                                <span class="font-semibold text-white">{{ kelvcmc_money($payment->amount) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-client-layout>
