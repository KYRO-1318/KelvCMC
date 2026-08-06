<?php

namespace App\Integrations\Proxmox;

use App\Payments\Concerns\InteractsWithHttp;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin client for the Proxmox VE REST API v2 (API tokens).
 */
class ProxmoxClient
{
    use InteractsWithHttp;

    public function __construct(
        protected ?string $url = null,
        protected ?string $user = null,
        protected ?string $tokenName = null,
        protected ?string $tokenValue = null,
        protected bool $verifySsl = false,
    ) {
        $this->url = rtrim($this->url ?? (string) config('services.proxmox.url'), '/');
        $this->user ??= config('services.proxmox.user');
        $this->tokenName ??= config('services.proxmox.token_name');
        $this->tokenValue ??= config('services.proxmox.token_value');
        $this->verifySsl = config('services.proxmox.verify_ssl', false);
    }

    public function isConfigured(): bool
    {
        return (bool) $this->url && $this->user && $this->tokenName && $this->tokenValue;
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl($this->url.'/api2/json')
            ->withHeaders([
                'Authorization' => 'PVEAPIToken='.$this->user.'!'.$this->tokenName.'='.$this->tokenValue,
            ])
            ->withOptions(['verify' => $this->verifySsl])
            ->asForm()
            ->acceptJson();
    }

    /** @return array<int, array<string, mixed>> */
    public function nodes(): array
    {
        $response = $this->client()->get('/nodes');
        $this->assertSuccess($response, 'Proxmox');

        return $response->json('data', []);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{vmid: string}
     */
    public function createVm(string $node, array $params): array
    {
        $response = $this->client()->post("/nodes/{$node}/qemu", $params);
        $this->assertSuccess($response, 'Proxmox');

        return [
            'vmid' => (string) ($params['vmid'] ?? ($response->json('data') ?? '')),
        ];
    }

    public function start(string $node, int $vmid): void
    {
        $response = $this->client()->post("/nodes/{$node}/qemu/{$vmid}/status/start");
        $this->assertSuccess($response, 'Proxmox');
    }

    public function stop(string $node, int $vmid): void
    {
        $response = $this->client()->post("/nodes/{$node}/qemu/{$vmid}/status/stop");
        $this->assertSuccess($response, 'Proxmox');
    }

    public function resizeDisk(string $node, int $vmid, string $sizeDelta): void
    {
        $response = $this->client()->put("/nodes/{$node}/qemu/{$vmid}/resize", [
            'disk' => 'scsi0',
            'size' => $sizeDelta,
        ]);
        $this->assertSuccess($response, 'Proxmox');
    }

    public function delete(string $node, int $vmid): void
    {
        $response = $this->client()->delete("/nodes/{$node}/qemu/{$vmid}");
        $this->assertSuccess($response, 'Proxmox');
    }

    /** @return int The next free vmid. */
    public function nextVmid(): int
    {
        $response = $this->client()->get('/cluster/nextid');
        $this->assertSuccess($response, 'Proxmox');

        return (int) $response->json('data');
    }
}
