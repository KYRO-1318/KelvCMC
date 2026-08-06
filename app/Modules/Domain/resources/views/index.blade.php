<x-client-layout title="Domain tools">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-white">Domain tools</h1>
            <p class="mt-1 text-sm text-slate-400">Check availability and manage DNS records through {{ $dnsEnabled ? 'your configured DNS provider' : 'WHOIS/DNS lookups' }}.</p>
        </div>

        <div class="card">
            <h2 class="font-medium text-white">Check availability</h2>
            <form action="{{ route('modules.domain.check') }}" method="POST" class="mt-4 flex gap-3">
                @csrf
                <input name="domain" required placeholder="example.com"
                    class="input flex-1" />
                <button type="submit" class="btn-primary">Check</button>
            </form>

            @if (session('domain'))
                <div class="mt-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm
                    {{ session('domain_available') ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300' }}">
                    <span class="h-2 w-2 rounded-full {{ session('domain_available') ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                    <span>
                        <strong>{{ session('domain') }}</strong> is
                        {{ session('domain_available') ? 'available' : 'registered' }}
                    </span>
                </div>
            @endif
        </div>

        @if ($dnsEnabled)
            <div class="card">
                <h2 class="font-medium text-white">Create DNS record</h2>
                <form action="{{ route('modules.domain.dns') }}" method="POST" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @csrf
                    <input name="domain" placeholder="example.com" class="input" required />
                    <select name="type" class="input">
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="CNAME">CNAME</option>
                        <option value="MX">MX</option>
                        <option value="TXT">TXT</option>
                    </select>
                    <input name="name" placeholder="Record name (e.g. www or @)" class="input" required />
                    <input name="content" placeholder="Value / IP" class="input" required />
                    <input name="ttl" type="number" placeholder="TTL (seconds)" class="input" />
                    <button type="submit" class="btn-primary">Create record</button>
                </form>
            </div>
        @endif

        @if (session('records'))
            <div class="card">
                <h2 class="font-medium text-white">Records for {{ session('domain') }}</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-slate-400">
                            <tr>
                                <th class="py-2 pr-4">Type</th>
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2">Content</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-300">
                            @forelse (session('records') as $record)
                                <tr class="border-t border-slate-800">
                                    <td class="py-2 pr-4"><span class="badge">{{ $record['type'] ?? $record['type'] ?? '?' }}</span></td>
                                    <td class="py-2 pr-4">{{ $record['name'] ?? ($record['name'] ?? '-') }}</td>
                                    <td class="py-2">{{ $record['content'] ?? ($record['records'][0]['content'] ?? '-') }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-slate-800">
                                    <td colspan="3" class="py-3 text-slate-500">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-client-layout>
