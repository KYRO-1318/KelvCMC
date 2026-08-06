<?php

namespace App\Integrations\Dns;

use App\Integrations\Contracts\DnsProvider;
use App\Payments\Concerns\InteractsWithHttp;
use Illuminate\Http\Client\PendingRequest;

class CloudflareClient implements DnsProvider
{
    use InteractsWithHttp;

    public function __construct(
        protected ?string $token = null,
        protected ?string $zoneId = null,
    ) {
        $this->token ??= config('services.cloudflare.api_token');
        $this->zoneId ??= config('services.cloudflare.zone_id');
    }

    public function name(): string
    {
        return 'Cloudflare';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.cloudflare.enabled') && (bool) $this->token;
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl('https://api.cloudflare.com/client/v4')
            ->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->asJson()
            ->acceptJson();
    }

    public function listZones(): array
    {
        $response = $this->client()->get('/zones');
        $this->assertSuccess($response, 'Cloudflare');

        return $response->json('result', []);
    }

    public function createZone(string $domain): array
    {
        $response = $this->client()->post('/zones', [
            'name' => $domain,
            'type' => 'full',
        ]);
        $this->assertSuccess($response, 'Cloudflare');

        return $response->json('result');
    }

    public function createRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600): array
    {
        $zoneId = $this->zoneId ?? $this->resolveZoneId($domain);

        $response = $this->client()->post("/zones/{$zoneId}/dns_records", [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl,
            'proxied' => false,
        ]);
        $this->assertSuccess($response, 'Cloudflare');

        return $response->json('result');
    }

    public function listRecords(string $domain): array
    {
        $zoneId = $this->zoneId ?? $this->resolveZoneId($domain);

        $response = $this->client()->get("/zones/{$zoneId}/dns_records", ['per_page' => 100]);
        $this->assertSuccess($response, 'Cloudflare');

        return $response->json('result', []);
    }

    public function deleteRecord(string $domain, string $recordId): void
    {
        $zoneId = $this->zoneId ?? $this->resolveZoneId($domain);

        $response = $this->client()->delete("/zones/{$zoneId}/dns_records/{$recordId}");
        $this->assertSuccess($response, 'Cloudflare');
    }

    private function resolveZoneId(string $domain): string
    {
        $zone = collect($this->listZones())->first(fn ($z) => str_ends_with($domain, $z['name']));

        if (! $zone) {
            throw new \RuntimeException('Cloudflare zone not found for '.$domain);
        }

        return $zone['id'];
    }
}
