<x-client-layout title="Support">
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">Support tickets</h1>
                <p class="mt-1 text-sm text-slate-400">We usually reply within a few hours.</p>
            </div>
            <a href="{{ route('tickets.create') }}" class="btn-primary">Open a ticket</a>
        </div>

        <div class="card overflow-hidden !p-0">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-800 bg-slate-900/60 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Subject</th>
                        <th class="px-5 py-3 font-medium">Priority</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Last activity</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-b border-slate-800/60 transition hover:bg-slate-900/40">
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-slate-200">{{ $ticket->subject }}</div>
                                <div class="text-xs text-slate-500">{{ $ticket->number }}</div>
                            </td>
                            <td class="px-5 py-3.5"><span class="badge">{{ ucfirst($ticket->priority) }}</span></td>
                            <td class="px-5 py-3.5"><span class="badge {{ $ticket->status === 'answered' ? '!bg-emerald-500/15 !text-emerald-300' : '' }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span></td>
                            <td class="px-5 py-3.5 text-slate-400">{{ $ticket->last_reply_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-400 hover:text-violet-300">Open <span aria-hidden="true">→</span></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No tickets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links() }}
    </div>
</x-client-layout>
