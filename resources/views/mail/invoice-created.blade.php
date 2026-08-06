<x-mail-layout>
    <h1>New invoice {{ $mailData['invoice']->number }}</h1>
    <p>Hi {{ $mailData['user']->name }}, a new invoice has been issued to your account.</p>
    <div class="box">
        <strong>Amount due:</strong> {{ kelvcmc_money($mailData['invoice']->total, $mailData['invoice']->currency) }}<br>
        <strong>Due date:</strong> {{ $mailData['invoice']->due_at?->format('d M Y') ?? 'Immediate' }}
    </div>
    <a class="btn" href="{{ route('billing.index') }}">View & pay invoice</a>
</x-mail-layout>
