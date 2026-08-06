<?php

namespace App\Integrations\Pterodactyl;

use App\Payments\Concerns\InteractsWithHttp;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin client for the Pterodactyl Admin (Application) API v1.
 */
class PterodactylClient
{
    use InteractsWithHttp;

    public function __construct(
        protected ?string $url = null,
        protected ?string $apiKey = null,
    ) {
        $this->url = rtrim($this->url ?? (string) config('services.pterodactyl.url'), '/');
        $this->apiKey ??= config('services.pterodactyl.api_key');
    }

    public function isConfigured(): bool
    {
        return (bool) $this->url && $this->apiKey;
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl($this->url)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
            ])
            ->asJson()
            ->acceptJson();
    }

    /** @return array<int, array<string, mixed>> */
    public function nodes(): array
    {
        $response = $this->client()->get('/api/application/nodes');
        $this->assertSuccess($response, 'Pterodactyl');

        return $response->json('data', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function locations(): array
    {
        $response = $this->client()->get('/api/application/locations');
        $this->assertSuccess($response, 'Pterodactyl');

        return $response->json('data', []);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{id: string, identifier: string}
     */
    public function createServer(array $params): array
    {
        $response = $this->client()->post('/api/application/servers', $params);
        $this->assertSuccess($response, 'Pterodactyl');

        $data = $response->json();

        return [
            'id' => (string) $data['attributes']['id'],
            'identifier' => (string) $data['attributes']['identifier'],
        ];
    }

    public function suspend(int $serverId): void
    {
        $response = $this->client()->post("/api/application/servers/{$serverId}/suspend");
        $this->assertSuccess($response, 'Pterodactyl');
    }

    public function unsuspend(int $serverId): void
    {
        $response = $this->client()->post("/api/application/servers/{$serverId}/unsuspend");
        $this->assertSuccess($response, 'Pterodactyl');
    }

    public function delete(int $serverId): void
    {
        $response = $this->client()->delete("/api/application/servers/{$serverId}");
        $this->assertSuccess($response, 'Pterodactyl');
    }

    /** @return array<string, mixed>|null */
    public function server(int $serverId): ?array
    {
        $response = $this->client()->get("/api/application/servers/{$serverId}");

        if ($response->failed()) {
            return null;
        }

        return $response->json('attributes');
    }
}
