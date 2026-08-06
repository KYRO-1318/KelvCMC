<?php

namespace App\Services;

use App\Integrations\Contracts\DnsProvider;
use App\Integrations\Contracts\HostingProvider;
use App\Models\Service;

class IntegrationManager
{
    /**
     * @param  array<string, class-string<HostingProvider>>  $providers
     */
    public function __construct(protected array $providers = [])
    {
    }

    /** Register a provider under a module id (e.g. "plesk"). */
    public function register(string $module, string $providerClass): void
    {
        $this->providers[$module] = $providerClass;
    }

    public function provider(string $module): HostingProvider
    {
        $class = $this->providers[$module] ?? null;

        if ($class) {
            $provider = app($class);
            if ($provider->isEnabled()) {
                return $provider;
            }
        }

        return app($this->providers['manual']);
    }

    /** Resolve the provider for a service based on its product module. */
    public function forService(Service $service): HostingProvider
    {
        $module = $service->product?->module ?? $service->metadata['module'] ?? 'manual';

        return $this->provider($module);
    }

    /** @return array<string, HostingProvider> */
    public function all(): array
    {
        return array_map(fn ($class) => app($class), $this->providers);
    }

    public function dns(): ?DnsProvider
    {
        return app(DnsProvider::class);
    }
}
