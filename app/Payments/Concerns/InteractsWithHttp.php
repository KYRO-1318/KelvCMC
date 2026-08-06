<?php

namespace App\Payments\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait InteractsWithHttp
{
    protected function http(): PendingRequest
    {
        return Http::timeout(30)->withoutVerifying();
    }

    /** @throws ConnectionException */
    protected function send(string $method, string $url, PendingRequest $request, array $payload = []): Response
    {
        $response = match (strtoupper($method)) {
            'POST' => $request->post($url, $payload),
            'PUT' => $request->put($url, $payload),
            'PATCH' => $request->patch($url, $payload),
            'DELETE' => $request->delete($url),
            default => $request->get($url),
        };

        return $response;
    }

    protected function assertSuccess(Response $response, string $context): void
    {
        if ($response->failed()) {
            throw new \RuntimeException(
                sprintf('[%s] API error %s: %s', $context, $response->status(), mb_substr($response->body(), 0, 500))
            );
        }
    }
}
