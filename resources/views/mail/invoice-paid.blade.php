<x-mail-layout>
    <h1>Payment received 🎉</h1>
    <p>Hi {{ $mailData['user']->name }}, your payment for invoice <strong>{{ $mailData['invoice']->number }}</strong> has been received.</p>
    <div class="box">
        <strong>Amount paid:</strong> {{ kelvcmc_money($mailData['invoice']->total, $mailData['invoice']->currency) }}
    </div>
    <p>Thank you for your business!</p>
    <a class="btn" href="{{ route('dashboard') }}">Go to dashboard</a>
</x-mail-layout>
