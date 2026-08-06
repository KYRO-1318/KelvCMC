<?php

namespace App\Integrations;

use App\Integrations\Contracts\HostingProvider;
use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Fallback provider for products that are provisioned manually or not yet
 * connected to an API. Services are activated without external calls.
 */
class ManualHostingProvider implements HostingProvider
{
    public function name(): string
    {
        return 'Manual';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function provision(Service $service): array
    {
        $username = $service->username ?? 'kc_'.Str::lower(Str::random(6));

        return [
            'remote_id' => 'manual-'.$service->id,
            'username' => $username,
            'password' => $service->password ?? Str::random(16),
            'manual' => true,
        ];
    }

    public function suspend(Service $service): void
    {
        // no-op
    }

    public function unsuspend(Service $service): void
    {
        // no-op
    }

    public function terminate(Service $service): void
    {
        // no-op
    }
}
