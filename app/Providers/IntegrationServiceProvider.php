<?php

namespace App\Providers;

use App\Integrations\Dns\CloudflareClient;
use App\Integrations\Dns\DnsManager;
use App\Integrations\Dns\PowerDnsClient;
use App\Integrations\Contracts\DnsProvider;
use App\Integrations\ManualHostingProvider;
use App\Integrations\Plesk\PleskClient;
use App\Integrations\Plesk\PleskHostingProvider;
use App\Integrations\Proxmox\ProxmoxClient;
use App\Integrations\Proxmox\ProxmoxHostingProvider;
use App\Integrations\Pterodactyl\PterodactylClient;
use App\Integrations\Pterodactyl\PterodactylHostingProvider;
use App\Services\IntegrationManager;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntegrationManager::class, function () {
            return new IntegrationManager([
                'manual' => ManualHostingProvider::class,
                'plesk' => PleskHostingProvider::class,
                'pterodactyl' => PterodactylHostingProvider::class,
                'proxmox' => ProxmoxHostingProvider::class,
            ]);
        });

        $this->app->bind(PleskClient::class, fn () => new PleskClient);
        $this->app->bind(PterodactylClient::class, fn () => new PterodactylClient);
        $this->app->bind(ProxmoxClient::class, fn () => new ProxmoxClient);
        $this->app->bind(CloudflareClient::class, fn () => new CloudflareClient);
        $this->app->bind(PowerDnsClient::class, fn () => new PowerDnsClient);

        $this->app->singleton(DnsManager::class);
        $this->app->bind(DnsProvider::class, DnsManager::class);
    }
}
