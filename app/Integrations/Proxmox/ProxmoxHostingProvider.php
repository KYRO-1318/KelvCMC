<?php

namespace App\Integrations\Proxmox;

use App\Integrations\Contracts\HostingProvider;
use App\Models\Service;
use Illuminate\Support\Str;

class ProxmoxHostingProvider implements HostingProvider
{
    public function __construct(protected ProxmoxClient $client) {}

    public function name(): string
    {
        return 'Proxmox';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.proxmox.enabled');
    }

    public function provision(Service $service): array
    {
        $plan = $service->plan;
        $config = $service->metadata['config'] ?? [];

        $node = $config['node'] ?? $service->server?->hostname;

        if (! $node) {
            throw new \RuntimeException('Proxmox node is required (set it on the server or product config).');
        }

        $vmid = $config['vmid'] ?? $this->client->nextVmid();

        $cores = $plan?->cpu_cores ?? $config['cores'] ?? 2;
        $memory = $plan?->ram_mb ?? $config['memory'] ?? 2048;
        $diskGb = (int) ceil(($plan?->disk_mb ?? $config['disk'] ?? 20480) / 1024);
        $storage = $config['storage'] ?? 'local-lvm';

        $response = $this->client->createVm($node, [
            'vmid' => (int) $vmid,
            'name' => 'kc-'.$service->id.'-'.Str::lower(Str::slug($service->name)),
            'memory' => (int) $memory,
            'cores' => (int) $cores,
            'sockets' => 1,
            'ostype' => $config['ostype'] ?? 'l26',
            'net0' => 'virtio,bridge=vmbr0',
            'scsi0' => "{$storage}:{$diskGb}",
            'ide2' => ($config['iso'] ?? null) ? "{$storage}:iso/".$config['iso'].',media=cdrom' : 'none,media=cdrom',
            'boot' => 'order=scsi0',
            'onboot' => 1,
        ]);

        $this->client->start($node, (int) $vmid);

        $rootPassword = Str::random(20);

        return [
            'remote_id' => (string) $vmid,
            'username' => 'root',
            'password' => $rootPassword,
            'node' => $node,
            'vmid' => (string) $vmid,
            'proxmox_url' => rtrim((string) config('services.proxmox.url'), '/').'/?console=kvm&vmid='.$vmid.'&node='.$node,
        ];
    }

    public function suspend(Service $service): void
    {
        $this->vmid($service, fn ($node, $vmid) => $this->client->stop($node, $vmid));
    }

    public function unsuspend(Service $service): void
    {
        $this->vmid($service, fn ($node, $vmid) => $this->client->start($node, $vmid));
    }

    public function terminate(Service $service): void
    {
        $this->vmid($service, fn ($node, $vmid) => $this->client->delete($node, $vmid));
    }

    private function vmid(Service $service, callable $callback): void
    {
        $node = $service->provisioning_data['node'] ?? null;
        $vmid = $service->remote_id;

        if (! $node || ! $vmid) {
            return;
        }

        $callback($node, (int) $vmid);
    }
}
