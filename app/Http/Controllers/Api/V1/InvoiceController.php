<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends ApiController
{
    public function index(Request $request)
    {
        $invoices = auth()->user()->invoices()
            ->with('items')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request));

        return $this->ok($invoices->items(), ['pagination' => [
            'total' => $invoices->total(),
            'per_page' => $invoices->perPage(),
            'current_page' => $invoices->currentPage(),
            'last_page' => $invoices->lastPage(),
        ]]);
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->user_id === auth()->id(), 403);

        return $this->ok($invoice->load(['items', 'payments']));
    }
}
