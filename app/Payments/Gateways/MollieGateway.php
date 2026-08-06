<?php

namespace App\Payments\Gateways;

use App\Payments\Concerns\InteractsWithHttp;
use App\Payments\Contracts\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class MollieGateway implements PaymentGateway
{
    use InteractsWithHttp;

    public function id(): string
    {
        return 'mollie';
    }

    public function name(): string
    {
        return 'Mollie';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.mollie.enabled') && (bool) config('services.mollie.api_key');
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http()
            ->withToken(config('services.mollie.api_key'))
            ->asJson()
            ->acceptJson();
    }

    public function createPayment(\App\Models\Payment $payment, \App\Models\Invoice $invoice, ?\App\Models\PaymentMethod $method = null): array
    {
        $response = $this->client()->post('https://api.mollie.com/v2/payments', [
            'amount' => [
                'currency' => $invoice->currency,
                'value' => number_format((float) $invoice->total, 2, '.', ''),
            ],
            'description' => 'Invoice '.$invoice->number,
            'redirectUrl' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'success']),
            'webhookUrl' => route('api.webhooks', ['gateway' => 'mollie']),
            'metadata' => ['reference' => $payment->reference],
        ]);

        $this->assertSuccess($response, 'Mollie');

        $data = $response->json();

        return [
            'transaction_id' => $data['id'],
            'redirect_url' => $data['_links']['checkout']['href'] ?? null,
            'metadata' => ['mollie_payment_id' => $data['id']],
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        if (! $payment->transaction_id) {
            return false;
        }

        $response = $this->client()->get('https://api.mollie.com/v2/payments/'.$payment->transaction_id);

        if ($response->failed()) {
            return false;
        }

        return $response->json('status') === 'paid';
    }

    public function refund(\App\Models\Payment $payment): bool
    {
        if (! $payment->transaction_id) {
            return false;
        }

        $response = $this->client()->post('https://api.mollie.com/v2/payments/'.$payment->transaction_id.'/refunds');

        return $response->successful();
    }

    public function handleWebhook(Request $request): mixed
    {
        $id = $request->input('id');

        if (! $id) {
            return response()->json(['error' => 'Missing id'], 400);
        }

        $payment = \App\Models\Payment::where('transaction_id', $id)->orWhere('metadata->mollie_payment_id', $id)->first();

        if ($payment) {
            if ($this->verify($payment)) {
                app(PaymentService::class)->complete($payment);
            } else {
                app(PaymentService::class)->fail($payment);
            }
        }

        return response()->json(['received' => true]);
    }
}
