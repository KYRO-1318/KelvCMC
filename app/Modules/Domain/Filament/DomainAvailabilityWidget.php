<?php

namespace App\Modules\Domain\Filament;

use Filament\Widgets\Widget;

class DomainAvailabilityWidget extends Widget
{
    protected static string $view = 'module-domain::filament.domain-availability';

    public ?string $domain = null;

    public ?bool $available = null;

    public function check(): void
    {
        $this->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i'],
        ]);

        $this->available = ! checkdnsrr(strtolower($this->domain), 'ANY');
    }
}
