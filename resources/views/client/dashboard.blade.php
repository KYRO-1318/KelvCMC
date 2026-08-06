<x-client-layout title="Dashboard">
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Welcome back, {{ $user->name }} 👋</h1>
                <p class="mt-1 text-sm text-slate-400">Here's an overview of your account.</p>
            </div>
            <a href="{{ route('store.index') }}" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Order a service
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active services</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $activeServices }}</p>
                    </div>
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/></svg></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Open invoices</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $openInvoices }}</p>
                    </div>
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z"/></svg></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ kelvcmc_money($openInvoicesTotal) }} due</p>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Open tickets</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $openTickets }}</p>
                    </div>
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Credit balance</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ kelvcmc_money($user->credit_balance) }}</p>
                    </div>
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg></div>
                </div>
            </div>
        </div>

        {{-- Chart + renewals --}}
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-white">Spending (last 6 months)</h2>
                    <a href="{{ route('invoices.index') }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">View invoices →</a>
                </div>
                <div class="mt-4 h-64">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-white">Upcoming renewals</h2>
                    <a href="{{ route('services.index') }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">All →</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($upcomingRenewals as $service)
                        <a href="{{ route('services.show', $service) }}" class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2.5 transition hover:border-violet-500/40">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-slate-200">{{ $service->name }}</div>
                                <div class="text-xs text-slate-500">{{ $service->expires_at?->diffForHumans() }}</div>
                            </div>
                            <span class="ml-2 text-sm font-semibold text-white">{{ kelvcmc_money($service->price) }}</span>
                        </a>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No upcoming renewals.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-white">Recent orders</h2>
                <a href="{{ route('store.index') }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">Browse store →</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentOrders as $order)
                    <div class="flex items-center justify-between rounded-lg border border-slate-800/80 bg-slate-900/40 px-3 py-3">
                        <div><div class="text-sm font-medium text-slate-200">{{ $order->number }}</div><div class="text-xs text-slate-500">{{ ($order->placed_at ?? $order->created_at)?->format('M j, Y') }}</div></div>
                        <div class="text-right"><div class="font-semibold text-white">{{ kelvcmc_money($order->total) }}</div><span class="badge">{{ ucfirst($order->status) }}</span></div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500">No orders yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent invoices --}}
        <div class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-white">Recent invoices</h2>
                <a href="{{ route('invoices.index') }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">View all →</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Number</th>
                            <th class="py-2 pr-4 font-medium">Date</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 text-right font-medium">Total</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $invoice)
                            <tr class="border-t border-slate-800/80">
                                <td class="py-3 pr-4 font-medium text-slate-200">{{ $invoice->number }}</td>
                                <td class="py-3 pr-4 text-slate-400">{{ $invoice->created_at->format('M j, Y') }}</td>
                                <td class="py-3 pr-4"><span class="badge {{ $invoice->status === 'paid' ? '!bg-emerald-500/15 !text-emerald-300' : ($invoice->status === 'overdue' ? '!bg-rose-500/15 !text-rose-300' : '') }}">{{ ucfirst($invoice->status) }}</span></td>
                                <td class="py-3 pr-4 text-right font-semibold text-white">{{ kelvcmc_money($invoice->total) }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-800/80">
                                <td colspan="5" class="py-8 text-center text-slate-500">No invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('spendingChart');
            if (!ctx || typeof Chart === 'undefined') return;

            const accent = getComputedStyle(document.documentElement).getPropertyValue('--k-accent').trim() || '#8b5cf6';

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartMonths),
                    datasets: [{
                        label: 'Spent',
                        data: @json($chartTotals),
                        borderColor: accent,
                        backgroundColor: accent + '33',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: accent,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#94a3b8' } },
                    },
                    scales: {
                        x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.08)' } },
                        y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.08)' }, beginAtZero: true },
                    },
                },
            });
        });
    </script>
    @endpush
</x-client-layout>
