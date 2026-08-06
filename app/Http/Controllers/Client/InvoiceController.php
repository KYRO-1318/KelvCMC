<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = auth()->user()->invoices()->with('items')->latest()->paginate(10);

        return view('client.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        return view('client.invoices.show', [
            'invoice' => $invoice->load(['items', 'payments', 'user']),
        ]);
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $invoice->load(['items', 'user']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper(config('kelvcmc.invoice_pdf.paper', 'a4'), config('kelvcmc.invoice_pdf.orientation', 'portrait'));

        return $pdf->download('invoice-'.$invoice->number.'.pdf');
    }

    protected function authorizeAccess(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }
}
