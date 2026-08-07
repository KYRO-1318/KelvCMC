<x-client-layout :title="$service->name">
    @php
        $provisioning = $service->provisioning_data ?? [];
    @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('services.index') }}" class="text-slate-500 transition hover:text-white">←</a>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ $service->name }}</h1>
                    <p class="text-sm text-slate-400">{{ $service->domain }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge {{ $service->status === 'active' ? '!bg-emerald-500/15 !text-emerald-300' : '' }}">{{ ucfirst($service->status) }}</span>
                @if ($service->status === 'active' || $service->status === 'pending')
                    <form method="POST" action="{{ route('services.action', $service) }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="renew">
                        <button type="submit" class="btn-secondary">Renew now</button>
                    </form>
                @endif
                @if (! in_array($service->status, ['terminated', 'cancelled']))
                    <form method="POST" action="{{ route('services.action', $service) }}" class="inline"
                          onsubmit="return confirm('Cancel this service at expiry? It will remain active until then.');">
                        @csrf
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-secondary !border-rose-500/30 !text-rose-300 hover:!bg-rose-500/10">Cancel at expiry</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($service->status === 'suspended')
            <div class="flex items-center gap-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                This service is suspended. Please settle any outstanding invoices to reactivate it.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Overview --}}
            <div class="card lg:col-span-2">
                <h2 class="font-semibold text-white">Overview</h2>
                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Product</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->product?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Plan</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->plan?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Billing cycle</dt><dd class="mt-1 text-sm text-slate-200">{{ ucfirst(str_replace('_', ' ', $service->billing_cycle)) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Price</dt><dd class="mt-1 text-sm text-slate-200">{{ kelvcmc_money($service->price) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Activated</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->activated_at?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Expires</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->expires_at?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Server</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->server?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-slate-500">Location</dt><dd class="mt-1 text-sm text-slate-200">{{ $service->server?->location ?? '—' }}</dd></div>
                </dl>

                @if ($service->plan?->specs())
                    <h3 class="mt-6 font-semibold text-white">Plan specifications</h3>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($service->plan->specs() as $label => $value)
                            <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
                                <div class="text-xs text-slate-500">{{ $label }}</div>
                                <div class="mt-0.5 text-sm font-semibold text-white">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Credentials --}}
            <div class="card">
                <h2 class="font-semibold text-white">Access details</h2>
                <div class="mt-4 space-y-4">
                    @if ($service->username)
                        <div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">Username</div>
                            <div class="mt-1 font-mono text-sm text-slate-200">{{ $service->username }}</div>
                        </div>
                    @endif
                    @if ($service->password)
                        <div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">Password</div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="font-mono text-sm text-slate-200" id="svc-password" data-placeholder="••••••••••••" data-endpoint="{{ route('services.credentials', $service) }}" data-revealed="0">••••••••••••</span>
                                <button type="button" data-reveal="#svc-password" class="text-xs font-medium text-violet-400 hover:text-violet-300">Show</button>
                            </div>
                        </div>
                    @endif
                    @if (isset($provisioning['panel_url']))
                        <a href="{{ $provisioning['panel_url'] }}" target="_blank" rel="noopener" class="btn-primary w-full">Open control panel</a>
                    @endif
                    @if (isset($provisioning['databases']) && is_array($provisioning['databases']))
                        <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3 text-xs text-slate-400">
                            <div class="font-semibold text-slate-300">Database</div>
                            <div class="mt-1 font-mono">DB: {{ $provisioning['databases']['name'] ?? '—' }}</div>
                            <div class="font-mono">User: {{ $provisioning['databases']['user'] ?? '—' }}</div>
                        </div>
                    @endif
                    <a href="{{ route('tickets.create') }}" class="btn-secondary w-full">Open a support ticket</a>
                </div>
            </div>
        </div>

        {{-- Invoice history --}}
        <div class="card">
            <h2 class="font-semibold text-white">Invoice history</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="py-2 pr-4 font-medium">Number</th><th class="py-2 pr-4 font-medium">Date</th><th class="py-2 pr-4 font-medium">Status</th><th class="py-2 pr-4 text-right font-medium">Total</th><th class="py-2"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($service->invoices as $invoice)
                            <tr class="border-t border-slate-800/80">
                                <td class="py-3 pr-4 text-slate-200">{{ $invoice->number }}</td>
                                <td class="py-3 pr-4 text-slate-400">{{ $invoice->created_at->format('M j, Y') }}</td>
                                <td class="py-3 pr-4"><span class="badge">{{ ucfirst($invoice->status) }}</span></td>
                                <td class="py-3 pr-4 text-right font-semibold text-white">{{ kelvcmc_money($invoice->total) }}</td>
                                <td class="py-3 text-right"><a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-medium text-violet-400 hover:text-violet-300">View →</a></td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-800/80"><td colspan="5" class="py-6 text-center text-slate-500">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-client-layout>
