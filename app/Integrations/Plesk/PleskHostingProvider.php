<?php

namespace App\Integrations\Plesk;

use App\Integrations\Contracts\HostingProvider;
use App\Models\Service;
use Illuminate\Support\Str;

class PleskHostingProvider implements HostingProvider
{
    public function __construct(protected PleskClient $client) {}

    public function name(): string
    {
        return 'Plesk';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.plesk.enabled');
    }

    public function provision(Service $service): array
    {
        $plan = $service->plan;
        $metadata = $service->metadata['config'] ?? [];

        // Stable login based on the user, unique per service.
        $login = 'kc_'.Str::lower(Str::limit(Str::slug($service->user->name), 8, '')).$service->id;
        $password = Str::random(16);
        $domain = $service->domain ?: ($metadata['domain'] ?? ($plan?->name ? Str::slug($plan->name).'.local' : 'service'.$service->id.'.local'));

        // Reuse an existing Plesk client for this user.
        $existing = $this->client->getClientByLogin($login);
        $client = $existing ?? $this->client->createClient($login, $password, $service->user->name, $service->user->company);

        $webspace = $this->client->createWebspace(
            $domain,
            (int) $client['id'],
            $service->server?->ip_address,
            isset($metadata['plesk_plan_id']) ? (int) $metadata['plesk_plan_id'] : null,
        );

        $databases = [];

        if ($plan && $plan->databases > 0) {
            $dbName = Str::lower(Str::slug($domain)).'_'.Str::random(4);
            $dbUser = 'kc_'.Str::lower(Str::random(6));
            $dbPassword = Str::random(20);

            $databases = $this->client->createDatabase((int) $webspace['id'], $dbName, $dbUser, $dbPassword);
            $databases['name'] = $dbName;
            $databases['user'] = $dbUser;
            $databases['password'] = $dbPassword;
        }

        if (! empty($metadata['ssl']) && $service->user->email) {
            $this->client->enableLetsEncrypt($domain, $service->user->email, (int) $webspace['id']);
        }

        return [
            'remote_id' => $webspace['id'],
            'username' => $login,
            'password' => $password,
            'plesk_client_id' => $client['id'],
            'plesk_client_guid' => $client['guid'] ?? null,
            'plesk_webspace_id' => $webspace['id'],
            'databases' => $databases,
            'domain' => $domain,
            'panel_url' => 'https://'.$service->server?->hostname.':8443',
        ];
    }

    public function suspend(Service $service): void
    {
        $id = $service->provisioning_data['plesk_webspace_id'] ?? $service->remote_id;

        if (! $id) {
            return;
        }

        $this->client->setWebspaceStatus((int) $id, 'disabled');
    }

    public function unsuspend(Service $service): void
    {
        $id = $service->provisioning_data['plesk_webspace_id'] ?? $service->remote_id;

        if (! $id) {
            return;
        }

        $this->client->setWebspaceStatus((int) $id, 'enabled');
    }

    public function terminate(Service $service): void
    {
        $webspaceId = $service->provisioning_data['plesk_webspace_id'] ?? $service->remote_id;
        $clientId = $service->provisioning_data['plesk_client_id'] ?? null;

        if ($webspaceId) {
            $this->client->deleteWebspace((int) $webspaceId);
        }

        if ($clientId) {
            $this->client->deleteClient((int) $clientId);
        }
    }
}
