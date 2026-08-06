<x-mail-layout>
    <h1>New reply on ticket {{ $mailData['ticket']->number }}</h1>
    <p>Hi {{ $mailData['user']->name }}, your support ticket has a new reply.</p>
    <div class="box">
        <strong>{{ $mailData['ticket']->subject }}</strong><br>
        <span style="color:#64748b">Last activity: {{ $mailData['ticket']->last_reply_at?->diffForHumans() }}</span>
    </div>
    <a class="btn" href="{{ route('tickets.show', $mailData['ticket']) }}">View ticket</a>
</x-mail-layout>
