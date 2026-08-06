<x-client-layout title="Invoices">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Invoices</h1>
            <p class="mt-1 text-sm text-slate-400">View and pay your invoices.</p>
        </div>

        <div class="card overflow-hidden !p-0">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-800 bg-slate-900/60 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Number</th>
                        <th class="px-5 py-3 font-medium">Date</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr class="border-b border-slate-800/60 transition hover:bg-slate-900/40">
                            <td class="px-5 py-3.5 font-medium text-slate-200">{{ $invoice->number }}</td>
                            <td class="px-5 py-3.5 text-slate-400">{{ $invoice->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="badge {{ $invoice->status === 'paid' ? '!bg-emerald-500/15 !text-emerald-300' : ($invoice->status === 'overdue' ? '!bg-rose-500/15 !text-rose-300' : ($invoice->status === 'open' ? '!bg-amber-500/15 !text-amber-300' : '')) }}">{{ ucfirst($invoice->status) }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-white">{{ kelvcmc_money($invoice->total) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-400 hover:text-violet-300">
                                    View <span aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $invoices->links() }}
    </div>
</x-client-layout>
