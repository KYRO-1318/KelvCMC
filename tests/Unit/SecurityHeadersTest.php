<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeadersTest extends TestCase
{
    public function test_baseline_headers_are_added(): void
    {
        $response = (new SecurityHeaders())->handle(
            Request::create('http://localhost/dashboard'),
            fn () => new Response('ok')
        );

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        self::assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_is_added_for_https_requests(): void
    {
        $response = (new SecurityHeaders())->handle(
            Request::create('https://example.test/dashboard'),
            fn () => new Response('ok')
        );

        self::assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security')
        );
    }
}
