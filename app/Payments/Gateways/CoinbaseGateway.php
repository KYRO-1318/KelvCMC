<?php

namespace App\Payments\Gateways;

use App\Payments\Concerns\InteractsWithHttp;
use App\Payments\Contracts\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CoinbaseGateway implements PaymentGateway
{
    use InteractsWithHttp;

    public function id(): string
    {
        return 'coinbase';
    }

    public function name(): string
    {
        return 'Coinbase Commerce';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.coinbase.enabled') && (bool) config('services.coinbase.api_key');
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http()
            ->withHeaders([
                'X-CC-Api-Key' => config('services.coinbase.api_key'),
                'X-CC-Version' => '2018-03-22',
            ])
            ->asJson()
            ->acceptJson();
    }

    public function createPayment(\App\Models\Payment $payment, \App\Models\Invoice $invoice, ?\App\Models\PaymentMethod $method = null): array
    {
        $response = $this->client()->post('https://api.commerce.coinbase.com/charges', [
            'name' => 'Invoice '.$invoice->number,
            'description' => 'Payment for invoice '.$invoice->number.' — '.$invoice->user->name,
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => number_format((float) $invoice->total, 2, '.', ''),
                'currency' => $invoice->currency,
            ],
            'metadata' => ['reference' => $payment->reference],
            'redirect_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'success']),
            'cancel_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'cancel']),
        ]);

        $this->assertSuccess($response, 'Coinbase');

        $data = $response->json('data');

        return [
            'transaction_id' => $data['id'],
            'redirect_url' => $data['hosted_url'] ?? null,
            'metadata' => ['charge_id' => $data['id'], 'code' => $data['code'] ?? null],
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        if (! $payment->transaction_id) {
            return false;
        }

        $response = $this->client()->get('https://api.commerce.coinbase.com/charges/'.$payment->transaction_id);

        if ($response->failed()) {
            return false;
        }

        return $response->json('data.timing') === 'COMPLETED';
    }

    public function refund(\App\Models\Payment $payment): bool
    {
        return false;
    }

    public function handleWebhook(Request $request): mixed
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Cc-Webhook-Signature');

        $computed = hash_hmac('sha256', $payload, (string) config('services.coinbase.webhook_secret'));

        if (! $signature || ! hash_equals($computed, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $type = $event['event']['type'] ?? '';
        $charge = $event['event']['data'] ?? [];
        $reference = $charge['metadata']['reference'] ?? null;

        if (in_array($type, ['charge:confirmed', 'charge:resolved'], true) && $reference) {
            $payment = \App\Models\Payment::where('reference', $reference)->first();

            if ($payment) {
                app(PaymentService::class)->complete($payment, $charge['id'] ?? null);
            }
        }

        return response()->json(['received' => true]);
    }
}
