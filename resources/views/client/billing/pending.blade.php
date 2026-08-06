<x-client-layout title="Payment pending">
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="card text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/15 text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="mt-4 text-xl font-bold text-white">Payment pending</h1>
            <p class="mt-1 text-sm text-slate-400">Your payment for <strong class="text-slate-200">{{ $payment->invoice?->number }}</strong> is awaiting confirmation ({{ $payment->gateway }}).</p>
        </div>

        @if ($payment->metadata['instructions'] ?? null)
            <div class="card">
                <h2 class="font-semibold text-white">How to complete your payment</h2>
                <pre class="mt-3 whitespace-pre-wrap rounded-lg border border-slate-800 bg-slate-900/60 p-4 font-mono text-sm text-slate-300">{!! e($payment->metadata['instructions']) !!}</pre>
                <p class="mt-3 text-xs text-slate-500">Your invoice will be marked as paid automatically once the transfer is confirmed by our team.</p>
            </div>
        @endif

        <div class="text-center">
            <a href="{{ route('billing.index') }}" class="btn-secondary">Back to billing</a>
        </div>
    </div>
</x-client-layout>
