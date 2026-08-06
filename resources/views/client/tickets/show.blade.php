<x-client-layout :title="$ticket->subject">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('tickets.index') }}" class="text-slate-500 transition hover:text-white">←</a>
                <div>
                    <h1 class="text-xl font-bold text-white">{{ $ticket->subject }}</h1>
                    <p class="text-xs text-slate-500">{{ $ticket->number }} · {{ $ticket->category?->name }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge">{{ ucfirst($ticket->priority) }} priority</span>
                <span class="badge {{ $ticket->status === 'answered' ? '!bg-emerald-500/15 !text-emerald-300' : '' }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                @if (! $ticket->isClosed())
                    <form method="POST" action="{{ route('tickets.close', $ticket) }}" onsubmit="return confirm('Close this ticket?');">
                        @csrf
                        <button type="submit" class="btn-secondary">Close ticket</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @foreach ($ticket->messages as $message)
                <div class="card {{ $message->is_admin ? '!border-violet-500/25' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white {{ $message->is_admin ? '' : 'bg-slate-700' }}" style="{{ $message->is_admin ? 'background: linear-gradient(135deg, var(--k-accent), var(--k-accent-strong))' : '' }}">
                            {{ strtoupper(substr($message->author_name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 text-sm font-semibold text-white">
                                {{ $message->author_name }}
                                @if ($message->is_admin)
                                    <span class="badge text-[10px]">Staff</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500">{{ $message->created_at->format('M j, Y · H:i') }}</div>
                        </div>
                    </div>
                    <div class="prose prose-sm prose-invert mt-3 max-w-none text-slate-300">{!! nl2br(e($message->body)) !!}</div>
                    @if ($message->attachments)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($message->attachments as $attachment)
                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-900 px-2.5 py-1 text-xs text-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                    {{ $attachment['name'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if (! $ticket->isClosed())
            <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="card">
                @csrf
                <label class="mb-1.5 block text-sm font-medium text-slate-300">Reply</label>
                <textarea name="message" rows="4" required class="input" placeholder="Type your reply..."></textarea>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <input type="file" name="attachments[]" multiple class="input !w-auto file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/20 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-violet-300">
                    <button type="submit" class="btn-primary">Send reply</button>
                </div>
            </form>
        @else
            <p class="text-center text-sm text-slate-500">This ticket is closed. Open a new one if you need further help.</p>
        @endif
    </div>
</x-client-layout>
