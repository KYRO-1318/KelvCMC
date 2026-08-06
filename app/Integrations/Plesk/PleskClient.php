<?php

namespace App\Integrations\Plesk;

use App\Payments\Concerns\InteractsWithHttp;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

/**
 * Thin client for the Plesk XML API (v1.6.3.0).
 * Requires a Plesk server with an admin/SSO account and API access enabled.
 */
class PleskClient
{
    use InteractsWithHttp;

    public function __construct(
        protected ?string $host = null,
        protected ?string $username = null,
        protected ?string $password = null,
        protected int $port = 8443,
        protected bool $verifySsl = false,
    ) {
        $this->host ??= config('services.plesk.host');
        $this->username ??= config('services.plesk.username');
        $this->password ??= config('services.plesk.password');
        $this->port = (int) (config('services.plesk.port') ?: $this->port);
        $this->verifySsl = config('services.plesk.verify_ssl', false);
    }

    public function isConfigured(): bool
    {
        return (bool) $this->host && $this->username && $this->password;
    }

    /** @return array{id: string, guid: string} */
    public function createClient(string $login, string $password, string $name, ?string $company = null): array
    {
        $response = $this->request([
            'customer' => [
                'add' => [
                    'cname' => $company ?: $name,
                    'pname' => $name,
                    'login' => $login,
                    'passwd' => $password,
                    'status' => '0',
                ],
            ],
        ]);

        $result = $response->customer->add->result;

        if ((string) $result->status !== 'ok') {
            throw new \RuntimeException('Plesk createClient: '.(string) $result->errtext);
        }

        return [
            'id' => (string) $result->id,
            'guid' => (string) $result->guid,
        ];
    }

    /** @return array{id: string} */
    public function createWebspace(string $domain, int $clientId, ?string $ip = null, ?int $planId = null): array
    {
        $genSetup = ['name' => $domain, 'htype' => 'vrt_hst'];

        if ($ip) {
            $genSetup['ip_address'] = $ip;
        }

        $add = ['gen_setup' => $genSetup];

        if ($planId) {
            $add['plan-name'] = $planId;
        }

        $response = $this->request(['webspace' => ['add' => $add]]);
        $result = $response->webspace->add->result;

        if ((string) $result->status !== 'ok') {
            throw new \RuntimeException('Plesk createWebspace: '.(string) $result->errtext);
        }

        return ['id' => (string) $result->id];
    }

    /** @return array{db_id: string, user_id: string} */
    public function createDatabase(int $webspaceId, string $dbName, string $dbUser, string $dbPassword): array
    {
        $dbResponse = $this->request([
            'database' => [
                'add-db' => [
                    'webspace-id' => $webspaceId,
                    'name' => $dbName,
                    'type' => 'mysql',
                ],
            ],
        ]);

        $dbResult = $dbResponse->database->{'add-db'}->result;

        if ((string) $dbResult->status !== 'ok') {
            throw new \RuntimeException('Plesk createDatabase: '.(string) $dbResult->errtext);
        }

        $dbId = (string) $dbResult->id;

        $userResponse = $this->request([
            'database' => [
                'add-user' => [
                    'db-id' => $dbId,
                    'login' => $dbUser,
                    'password' => $dbPassword,
                ],
            ],
        ]);

        $userResult = $userResponse->database->{'add-user'}->result;

        if ((string) $userResult->status !== 'ok') {
            throw new \RuntimeException('Plesk addDbUser: '.(string) $userResult->errtext);
        }

        return [
            'db_id' => $dbId,
            'user_id' => (string) $userResult->id,
        ];
    }

    /** @return array{id: string} */
    public function enableLetsEncrypt(string $domain, string $email, int $webspaceId): array
    {
        $response = $this->request([
            'certificate' => [
                'letsencrypt' => [
                    'install' => [
                        'name' => $domain,
                        'mail' => $email,
                        'webspace-id' => $webspaceId,
                    ],
                ],
            ],
        ]);

        $result = $response->certificate?->letsencrypt?->install?->result;

        if ($result && (string) $result->status !== 'ok') {
            throw new \RuntimeException('Plesk enableLetsEncrypt: '.(string) $result->errtext);
        }

        return ['id' => (string) ($result?->id ?? '')];
    }

    public function setWebspaceStatus(int $webspaceId, string $status): void
    {
        $response = $this->request([
            'webspace' => [
                'set' => [
                    'filter' => ['id' => $webspaceId],
                    'values' => ['status' => $status],
                ],
            ],
        ]);

        $result = $response->webspace->set->result;

        if ((string) $result->status !== 'ok') {
            throw new \RuntimeException('Plesk setWebspaceStatus: '.(string) $result->errtext);
        }
    }

    public function deleteWebspace(int $webspaceId): void
    {
        $response = $this->request([
            'webspace' => ['del' => ['filter' => ['id' => $webspaceId]]],
        ]);

        $result = $response->webspace->del->result;

        if ((string) $result->status !== 'ok' && (string) $result->status !== 'error') {
            throw new \RuntimeException('Plesk deleteWebspace failed');
        }
    }

    public function deleteClient(int $clientId): void
    {
        $response = $this->request([
            'customer' => ['del' => ['filter' => ['id' => $clientId]]],
        ]);

        $result = $response->customer->del->result;

        if ((string) $result->status !== 'ok' && (string) $result->status !== 'error') {
            throw new \RuntimeException('Plesk deleteClient failed');
        }
    }

    public function getClientByLogin(string $login): ?array
    {
        $response = $this->request([
            'customer' => ['get' => ['filter' => ['login' => $login]]],
        ]);

        $result = $response->customer->get->result;

        if ((string) $result->status !== 'ok' || ! isset($result->id)) {
            return null;
        }

        return [
            'id' => (string) $result->id,
            'guid' => (string) $result->guid,
            'login' => $login,
        ];
    }

    /**
     * Send a packet to the Plesk API and return the response root.
     */
    private function request(array $packet): SimpleXMLElement
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Plesk is not configured (PLESK_HOST/USERNAME/PASSWORD).');
        }

        $xml = $this->buildPacket($packet);

        $response = $this->http()
            ->withBasicAuth($this->username, $this->password)
            ->withHeaders(['Content-Type' => 'text/xml'])
            ->withOptions(['verify' => $this->verifySsl])
            ->send('POST', "https://{$this->host}:{$this->port}/enterprise/control/agent.php", ['body' => $xml]);

        $this->assertSuccess($response, 'Plesk');

        $parsed = simplexml_load_string($response->body());

        if ($parsed === false) {
            throw new \RuntimeException('Plesk returned an invalid XML response.');
        }

        return $parsed;
    }

    private function buildPacket(array $payload): string
    {
        $packet = new SimpleXMLElement('<packet version="1.6.3.0"/>');
        $this->arrayToXml($payload, $packet);

        return $packet->asXML();
    }

    private function arrayToXml(array $data, SimpleXMLElement $node): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $node->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $node->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }
}
