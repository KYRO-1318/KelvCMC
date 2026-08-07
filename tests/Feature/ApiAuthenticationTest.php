<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ApiAuthenticationTest extends TestCase
{
    public function test_protected_api_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/orders')
            ->assertUnauthorized();
    }
}
