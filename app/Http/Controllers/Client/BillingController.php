<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(protected PaymentService $payments, protected PaymentGatewayManager $gateways) {}

    public function index()
    {
        $user = auth()->user();

        return view('client.billing.index', [
            'user' => $user,
            'openInvoices' => $user->openInvoices()->with('items')->latest()->get(),
            'recentPayments' => $user->payments()->latest()->limit(8)->get(),
            'creditTransactions' => $user->creditTransactions()->latest()->limit(8)->get(),
            'gateways' => $this->gateways->forUser($user),
        ]);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
        abort_unless($invoice->isPayable(), 422, 'This invoice cannot be paid.');

        $validated = $request->validate([
            'gateway' => ['required', 'string'],
        ]);

        try {
            $result = $this->payments->initiate($invoice, $validated['gateway']);
        } catch (\RuntimeException $e) {
            report($e);

            return back()->withErrors(['gateway' => 'This payment method is currently unavailable.']);
        }

        if ($result['payment']->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice paid. Thank you!');
        }

        if ($result['redirect_url']) {
            return redirect()->away($result['redirect_url']);
        }

        // Offline gateways (bank transfer) show the return screen with instructions.
        return redirect()->route('billing.payment.return', [
            'reference' => $result['payment']->reference,
            'status' => 'pending',
        ]);
    }

    public function return(Request $request, string $reference)
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();
        abort_unless($payment->user_id === auth()->id(), 403);

        $status = $request->query('status', 'pending');

        if ($status === 'success' && $payment->status !== 'paid') {
            $gateway = $this->gateways->get($payment->gateway);

            if ($gateway && $gateway->verify($payment)) {
                $this->payments->complete($payment, $payment->transaction_id);

                return redirect()->route('invoices.show', $payment->invoice)
                    ->with('success', 'Payment confirmed. Thank you!');
            }

            return redirect()->route('billing.index')->withErrors(['payment' => 'We could not confirm the payment. Please contact support.']);
        }

        if ($status === 'cancel') {
            $this->payments->cancel($payment);

            return redirect()->route('billing.index')->with('info', 'Payment cancelled.');
        }

        // pending → show instructions (bank transfer).
        return view('client.billing.pending', [
            'payment' => $payment->load('invoice'),
        ]);
    }
}
