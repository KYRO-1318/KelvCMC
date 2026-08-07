<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\BillingService;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = auth()->user()->services()->with(['product', 'plan', 'server'])->latest()->paginate(10);

        return view('client.services.index', compact('services'));
    }

    public function show(Service $service)
    {
        $this->authorizeAccess($service);

        return view('client.services.show', [
            'service' => $service->load(['product', 'plan', 'server', 'invoices']),
        ]);
    }

    public function credentials(Service $service)
    {
        $this->authorizeAccess($service);

        abort_unless($service->password, 404);

        return response()
            ->json(['password' => $service->password])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function action(Request $request, Service $service, ProvisioningService $provisioning, BillingService $billing)
    {
        $this->authorizeAccess($service);

        $action = $request->input('action');

        match ($action) {
            'renew' => $this->renew($service, $billing),
            'cancel' => $this->cancel($service),
            'unsuspend' => $provisioning->unsuspend($service),
            default => throw ValidationException::withMessages(['action' => 'Unknown action.']),
        };

        return back()->with('success', 'Service updated.');
    }

    protected function renew(Service $service, BillingService $billing): void
    {
        $billing->createRenewalInvoice($service, $service->billing_cycle);
    }

    protected function cancel(Service $service): void
    {
        if ($service->status === 'terminated') {
            return;
        }

        // Mark for cancellation at expiry.
        $service->update([
            'metadata' => array_merge($service->metadata ?? [], ['cancel_at_expiry' => true]),
        ]);
    }

    protected function authorizeAccess(Service $service): void
    {
        abort_unless($service->user_id === auth()->id(), 403);
    }
}
