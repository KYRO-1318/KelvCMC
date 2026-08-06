<x-mail-layout>
    <h1>Payment reminder</h1>
    <p>Hi {{ $mailData['user']->name }}, this is a reminder that invoice <strong>{{ $mailData['invoice']->number }}</strong> is due on {{ $mailData['invoice']->due_at?->format('d M Y') }}.</p>
    <div class="box">
        <strong>Amount due:</strong> {{ kelvcmc_money($mailData['invoice']->total, $mailData['invoice']->currency) }}<br>
        <strong>Status:</strong> {{ ucfirst($mailData['invoice']->status) }}
    </div>
    <a class="btn" href="{{ route('billing.index') }}">Pay now</a>
</x-mail-layout>
