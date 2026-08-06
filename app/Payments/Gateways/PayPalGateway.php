<?php

namespace App\Payments\Gateways;

use App\Payments\Concerns\InteractsWithHttp;
use App\Payments\Contracts\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PayPalGateway implements PaymentGateway
{
    use InteractsWithHttp;

    public function id(): string
    {
        return 'paypal';
    }

    public function name(): string
    {
        return 'PayPal';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.paypal.enabled')
            && (bool) config('services.paypal.client_id')
            && (bool) config('services.paypal.secret');
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    private function baseUrl(): string
    {
        return config('services.paypal.sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function accessToken(): string
    {
        $response = $this->http()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->asForm()
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        $this->assertSuccess($response, 'PayPal');

        return $response->json('access_token');
    }

    public function createPayment(\App\Models\Payment $payment, \App\Models\Invoice $invoice, ?\App\Models\PaymentMethod $method = null): array
    {
        $token = $this->accessToken();

        $response = $this->http()
            ->withToken($token)
            ->asJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payment->reference,
                    'custom_id' => $payment->reference,
                    'amount' => [
                        'currency_code' => $invoice->currency,
                        'value' => number_format((float) $invoice->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'success']),
                    'cancel_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'cancel']),
                    'brand_name' => kelvcmc_brand(),
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        $this->assertSuccess($response, 'PayPal');

        $order = $response->json();
        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        return [
            'transaction_id' => $order['id'],
            'redirect_url' => $approveLink['href'] ?? null,
            'metadata' => ['order_id' => $order['id']],
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        if (! $payment->transaction_id) {
            return false;
        }

        $token = $this->accessToken();
        $response = $this->http()
            ->withToken($token)
            ->post($this->baseUrl().'/v2/checkout/orders/'.$payment->transaction_id.'/capture');

        if ($response->failed()) {
            return false;
        }

        $status = $response->json('status');

        return $status === 'COMPLETED';
    }

    public function refund(\App\Models\Payment $payment): bool
    {
        $captureId = $payment->metadata['capture_id'] ?? null;

        if (! $captureId) {
            return false;
        }

        $token = $this->accessToken();
        $response = $this->http()
            ->withToken($token)
            ->asJson()
            ->post($this->baseUrl().'/v2/payments/captures/'.$captureId.'/refund', [
                'amount' => [
                    'currency_code' => $payment->currency,
                    'value' => number_format((float) $payment->amount, 2, '.', ''),
                ],
            ]);

        return $response->successful();
    }

    public function handleWebhook(Request $request): mixed
    {
        // PayPal webhook verification requires re-sending the headers to PayPal;
        // for simplicity the return-URL flow (verify + capture) is the primary path.
        $reference = $request->query('payment');

        return response()->json(['received' => true, 'reference' => $reference]);
    }
}
