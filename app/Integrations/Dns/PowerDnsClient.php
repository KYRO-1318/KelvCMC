<?php

namespace App\Integrations\Dns;

use App\Integrations\Contracts\DnsProvider;
use App\Payments\Concerns\InteractsWithHttp;
use Illuminate\Http\Client\PendingRequest;

class PowerDnsClient implements DnsProvider
{
    use InteractsWithHttp;

    public function __construct(
        protected ?string $url = null,
        protected ?string $apiKey = null,
    ) {
        $this->url = rtrim($this->url ?? (string) config('services.powerdns.url'), '/');
        $this->apiKey ??= config('services.powerdns.api_key');
    }

    public function name(): string
    {
        return 'PowerDNS';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.powerdns.enabled') && (bool) $this->url && (bool) $this->apiKey;
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl($this->url.'/api/v1/servers/localhost')
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->asJson()
            ->acceptJson();
    }

    public function listZones(): array
    {
        $response = $this->client()->get('/zones');
        $this->assertSuccess($response, 'PowerDNS');

        return $response->json();
    }

    public function createZone(string $domain): array
    {
        $response = $this->client()->post('/zones', [
            'name' => rtrim($domain, '.').'.',
            'kind' => 'Native',
            'soa_edit_api' => 'DEFAULT',
            'nameservers' => [],
        ]);
        $this->assertSuccess($response, 'PowerDNS');

        return $response->json();
    }

    public function createRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600): array
    {
        $zone = rtrim($domain, '.').'.';

        $response = $this->client()->patch("/zones/{$zone}", [
            'rrsets' => [[
                'name' => $name.'.',
                'type' => strtoupper($type),
                'ttl' => $ttl,
                'changetype' => 'REPLACE',
                'records' => [['content' => $content, 'disabled' => false]],
            ]],
        ]);
        $this->assertSuccess($response, 'PowerDNS');

        return $response->json();
    }

    public function listRecords(string $domain): array
    {
        $zone = rtrim($domain, '.').'.';

        $response = $this->client()->get('/zones/'.$zone);
        $this->assertSuccess($response, 'PowerDNS');

        return $response->json('rrsets', []);
    }

    public function deleteRecord(string $domain, string $recordName): void
    {
        $zone = rtrim($domain, '.').'.';

        $response = $this->client()->patch("/zones/{$zone}", [
            'rrsets' => [[
                'name' => $recordName.'.',
                'type' => 'A',
                'changetype' => 'DELETE',
            ]],
        ]);
        $this->assertSuccess($response, 'PowerDNS');
    }
}
