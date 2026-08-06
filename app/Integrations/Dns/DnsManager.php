<?php

namespace App\Integrations\Dns;

use App\Integrations\Contracts\DnsProvider;

/**
 * Delegates to whichever DNS provider is enabled (Cloudflare first, then
 * PowerDNS). Binds to the DnsProvider contract in the container.
 */
class DnsManager implements DnsProvider
{
    /** @var array<int, class-string<DnsProvider>> */
    protected array $providers = [
        CloudflareClient::class,
        PowerDnsClient::class,
    ];

    public function name(): string
    {
        return $this->active()?->name() ?? 'None';
    }

    public function isEnabled(): bool
    {
        return $this->active() !== null;
    }

    public function listZones(): array
    {
        return $this->delegate('listZones');
    }

    public function createZone(string $domain): array
    {
        return $this->delegate('createZone', $domain);
    }

    public function createRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600): array
    {
        return $this->delegate('createRecord', $domain, $type, $name, $content, $ttl);
    }

    public function listRecords(string $domain): array
    {
        return $this->delegate('listRecords', $domain);
    }

    public function deleteRecord(string $domain, string $recordId): void
    {
        $this->delegate('deleteRecord', $domain, $recordId);
    }

    private function active(): ?DnsProvider
    {
        foreach ($this->providers as $class) {
            $provider = app($class);

            if ($provider->isEnabled()) {
                return $provider;
            }
        }

        return null;
    }

    private function delegate(string $method, mixed ...$args): mixed
    {
        $provider = $this->active();

        if (! $provider) {
            throw new \RuntimeException('No DNS provider is enabled. Configure Cloudflare or PowerDNS.');
        }

        return $provider->{$method}(...$args);
    }
}
