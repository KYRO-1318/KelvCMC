<?php

namespace App\Integrations\Contracts;

interface DnsProvider
{
    public function name(): string;

    public function isEnabled(): bool;

    /** @return array<int, array<string, mixed>> */
    public function listZones(): array;

    /** @return array<string, mixed> */
    public function createZone(string $domain): array;

    /** @return array<string, mixed> */
    public function createRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600): array;

    /** @return array<int, array<string, mixed>> */
    public function listRecords(string $domain): array;

    public function deleteRecord(string $domain, string $recordId): void;
}
