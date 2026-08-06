<?php

namespace App\Payments\Gateways;

use App\Payments\Concerns\InteractsWithHttp;
use App\Payments\Contracts\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class StripeGateway implements PaymentGateway
{
    use InteractsWithHttp;

    public function id(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.stripe.enabled') && (bool) config('services.stripe.secret_key');
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function createPayment(\App\Models\Payment $payment, \App\Models\Invoice $invoice, ?\App\Models\PaymentMethod $method = null): array
    {
        $request = $this->http()
            ->withBasicAuth(config('services.stripe.secret_key'), '')
            ->asForm();

        $response = $this->send('POST', 'https://api.stripe.com/v1/checkout/sessions', $request, [
            'mode' => 'payment',
            'success_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'success']),
            'cancel_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'cancel']),
            'client_reference_id' => $payment->reference,
            'line_items[0][price_data][currency]' => strtolower($invoice->currency),
            'line_items[0][price_data][product_data][name]' => 'Invoice '.$invoice->number,
            'line_items[0][price_data][unit_amount]' => (int) round($invoice->total * 100),
            'line_items[0][quantity]' => 1,
        ]);

        $this->assertSuccess($response, 'Stripe');

        $session = $response->json();

        return [
            'transaction_id' => $session['id'],
            'redirect_url' => $session['url'],
            'metadata' => ['session_id' => $session['id']],
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        $request = $this->http()->withBasicAuth(config('services.stripe.secret_key'), '');
        $response = $this->send('GET', 'https://api.stripe.com/v1/checkout/sessions/'.$payment->transaction_id, $request);

        if ($response->failed()) {
            return false;
        }

        return ($response->json('payment_status') ?? $response->json('status')) === 'paid';
    }

    public function refund(\App\Models\Payment $payment): bool
    {
        $request = $this->http()->withBasicAuth(config('services.stripe.secret_key'), '')->asForm();
        $response = $this->send('POST', 'https://api.stripe.com/v1/refunds', $request, [
            'payment_intent' => $payment->metadata['payment_intent'] ?? null,
            'amount' => (int) round($payment->amount * 100),
        ]);

        return $response->successful();
    }

    public function handleWebhook(Request $request): mixed
    {
        $payload = $request->getContent();
        $header = $request->header('Stripe-Signature');
        $parts = $this->parseSignatureHeader($header);

        $timestamp = $parts['t'] ?? null;
        $received = $parts['v1'] ?? null;
        $secret = (string) config('services.stripe.webhook_secret');

        // Stripe signs "{timestamp}.{payload}" with HMAC-SHA256.
        $expected = $timestamp ? hash_hmac('sha256', $timestamp.'.'.$payload, $secret) : null;

        if (! $received || ! $expected || ! hash_equals($expected, $received)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Reject webhooks older than 5 minutes (replay protection).
        if (abs((int) $timestamp - time()) > 300) {
            return response()->json(['error' => 'Webhook too old'], 400);
        }

        $event = json_decode($payload, true);
        $type = $event['type'] ?? '';
        $session = $event['data']['object'] ?? [];

        if ($type === 'checkout.session.completed') {
            $payment = \App\Models\Payment::where('reference', $session['client_reference_id'] ?? null)->first();

            if ($payment) {
                app(PaymentService::class)->complete($payment, $session['payment_intent'] ?? null);
            }
        }

        return response()->json(['received' => true]);
    }

    /** @return array<string, string> */
    private function parseSignatureHeader(?string $header): array
    {
        $parts = [];

        foreach (explode(',', (string) $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            $parts[$key] = $value;
        }

        return $parts;
    }
}
