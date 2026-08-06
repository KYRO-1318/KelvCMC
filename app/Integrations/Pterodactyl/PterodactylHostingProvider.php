<?php

namespace App\Integrations\Pterodactyl;

use App\Integrations\Contracts\HostingProvider;
use App\Models\Service;
use Illuminate\Support\Str;

class PterodactylHostingProvider implements HostingProvider
{
    public function __construct(protected PterodactylClient $client) {}

    public function name(): string
    {
        return 'Pterodactyl';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.pterodactyl.enabled');
    }

    public function provision(Service $service): array
    {
        $plan = $service->plan;
        $config = $service->metadata['config'] ?? [];
        $server = $service->server;

        // Pterodactyl needs an existing client user; we use the order config
        // or default to the first admin user via the egg/environment.
        $userId = $config['pterodactyl_user_id'] ?? null;

        if (! $userId) {
            throw new \RuntimeException('Pterodactyl user id missing. Set it in the product config (pterodactyl_user_id).');
        }

        $egg = $config['egg_id'] ?? $config['pterodactyl_egg'] ?? 1;
        $dockerImage = $config['docker_image'] ?? ($service->product?->type === 'fivem' ? 'ghcr.io/parkervcp/yolks:games_fivem' : 'ghcr.io/parkervcp/yolks:games_minecraft');

        $nodeId = $config['node_id'] ?? $server?->remote_id;
        $allocation = $config['allocation_id'] ?? null;

        if (! $nodeId || ! $allocation) {
            throw new \RuntimeException('Pterodactyl node_id and allocation_id are required (set them on the product config or server).');
        }

        $memory = $plan?->ram_mb ?? $config['memory'] ?? 1024;
        $swap = $plan?->swap_mb ?? $config['swap'] ?? 0;
        $disk = $plan?->disk_mb ?? $config['disk'] ?? 10240;
        $cpu = $config['cpu'] ?? 100;
        $io = $config['io'] ?? 500;

        $response = $this->client->createServer([
            'name' => $service->name,
            'user' => (int) $userId,
            'egg' => (int) $egg,
            'docker_image' => $dockerImage,
            'startup' => $config['startup'] ?? null,
            'environment' => $config['environment'] ?? [
                'SERVER_NAME' => $service->name,
                'SERVER_PORT' => $config['server_port'] ?? '25565',
            ],
            'limits' => [
                'memory' => (int) $memory,
                'swap' => (int) $swap,
                'disk' => (int) $disk,
                'io' => (int) $io,
                'cpu' => (int) $cpu,
            ],
            'feature_limits' => [
                'databases' => $config['databases'] ?? 0,
                'allocations' => $config['allocations'] ?? 1,
                'backups' => $config['backups'] ?? 0,
            ],
            'allocation' => [
                'default' => (int) $allocation,
                'additional' => $config['additional_allocations'] ?? [],
            ],
        ]);

        return [
            'remote_id' => $response['id'],
            'identifier' => $response['identifier'],
            'username' => $service->user->name,
            'panel_url' => rtrim((string) config('services.pterodactyl.url'), '/').'/server/'.$response['identifier'],
        ];
    }

    public function suspend(Service $service): void
    {
        if (! $service->remote_id) {
            return;
        }

        $this->client->suspend((int) $service->remote_id);
    }

    public function unsuspend(Service $service): void
    {
        if (! $service->remote_id) {
            return;
        }

        $this->client->unsuspend((int) $service->remote_id);
    }

    public function terminate(Service $service): void
    {
        if (! $service->remote_id) {
            return;
        }

        $this->client->delete((int) $service->remote_id);
    }
}
