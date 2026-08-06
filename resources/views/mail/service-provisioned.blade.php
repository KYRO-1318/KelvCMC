<x-mail-layout>
    <h1>{{ $mailData['service']->name }} is live 🚀</h1>
    <p>Hi {{ $mailData['user']->name }}, your service has been provisioned and is now active.</p>
    <div class="box">
        @if ($mailData['service']->domain)<strong>Domain:</strong> {{ $mailData['service']->domain }}<br>@endif
        @if ($mailData['service']->username)<strong>Username:</strong> {{ $mailData['service']->username }}<br>@endif
        @if (isset($mailData['service']->provisioning_data['panel_url']))<strong>Panel:</strong> {{ $mailData['service']->provisioning_data['panel_url'] }}@endif
    </div>
    <a class="btn" href="{{ route('services.show', $mailData['service']) }}">View my service</a>
</x-mail-layout>
