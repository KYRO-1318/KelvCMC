<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(string $gateway, Request $request, PaymentService $payments)
    {
        return $payments->handleWebhook($gateway, $request);
    }
}
