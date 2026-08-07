<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthSecurityHeadersTest extends TestCase
{
    public function test_health_endpoint_is_available_with_security_headers(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
