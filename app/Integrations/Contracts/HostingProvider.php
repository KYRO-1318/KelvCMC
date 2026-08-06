<?php

namespace App\Integrations\Contracts;

use App\Models\Service;

/**
 * Contract implemented by every hosting integration (Plesk, Pterodactyl,
 * Proxmox, ...). Returns provisioning details to store on the service.
 */
interface HostingProvider
{
    public function name(): string;

    public function isEnabled(): bool;

    /**
     * @return array<string, mixed>  remote_id, username, password, plus provider details
     */
    public function provision(Service $service): array;

    public function suspend(Service $service): void;

    public function unsuspend(Service $service): void;

    public function terminate(Service $service): void;
}
