<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProvisioningService
{
    public function __construct(
        protected IntegrationManager $integrations,
        protected NotificationService $notifications,
    ) {}

    /**
     * Create a pending service record from an order item.
     */
    public function createPendingService(\App\Models\User $user, \App\Models\OrderItem $item, ?\App\Models\Invoice $invoice = null): Service
    {
        $product = $item->product;
        $plan = $item->plan;

        $name = $item->description;
        $domain = $item->config['domain'] ?? null;

        $service = Service::create([
            'user_id' => $user->id,
            'order_item_id' => $item->id,
            'product_id' => $product?->id,
            'plan_id' => $plan?->id,
            'server_group_id' => $product?->server_group_id,
            'name' => $name,
            'domain' => $domain,
            'status' => ServiceStatus::Pending->value,
            'billing_cycle' => $item->billing_cycle,
            'price' => $item->unit_price,
            'metadata' => ['config' => $item->config ?? []],
        ]);

        return $service;
    }

    /**
     * Queue provisioning for every pending service of an order.
     */
    public function provisionPendingForOrder(\App\Models\Order $order): void
    {
        $services = Service::whereIn('order_item_id', $order->items->pluck('id'))->get();

        foreach ($services as $service) {
            $this->provision($service);
        }
    }

    /**
     * Provision a single service through its integration driver.
     */
    public function provision(Service $service): void
    {
        if (! in_array($service->status, [ServiceStatus::Pending->value, ServiceStatus::Active->value])) {
            return;
        }

        try {
            $provider = $this->integrations->forService($service);
            $result = $provider->provision($service);

            $service->update([
                'status' => ServiceStatus::Active->value,
                'remote_id' => $result['remote_id'] ?? $service->remote_id,
                'username' => $result['username'] ?? $service->username,
                'password' => $result['password'] ?? $service->password,
                'provisioning_data' => array_merge($service->provisioning_data ?? [], $result),
                'activated_at' => now(),
                'expires_at' => $this->expiryFromCycle($service->billing_cycle),
                'suspended_at' => null,
            ]);

            $this->notifications->serviceProvisioned($service);
        } catch (\Throwable $e) {
            report($e);
            // Keep the service pending; a failed job will retry.
            \App\Models\AuditLog::create([
                'action' => 'service.provision.failed',
                'model_type' => $service->getMorphClass(),
                'model_id' => $service->id,
                'metadata' => ['error' => $e->getMessage()],
            ]);
        }
    }

    public function suspend(Service $service, ?string $reason = null): void
    {
        if ($service->status === ServiceStatus::Suspended->value) {
            return;
        }

        try {
            $this->integrations->forService($service)->suspend($service);
        } catch (\Throwable $e) {
            report($e);
        }

        $service->update([
            'status' => ServiceStatus::Suspended->value,
            'suspended_at' => now(),
            'metadata' => array_merge($service->metadata ?? [], ['suspend_reason' => $reason]),
        ]);
    }

    public function unsuspend(Service $service): void
    {
        if ($service->status !== ServiceStatus::Suspended->value) {
            return;
        }

        try {
            $this->integrations->forService($service)->unsuspend($service);
        } catch (\Throwable $e) {
            report($e);
        }

        $service->update([
            'status' => ServiceStatus::Active->value,
            'suspended_at' => null,
        ]);
    }

    public function terminate(Service $service): void
    {
        try {
            $this->integrations->forService($service)->terminate($service);
        } catch (\Throwable $e) {
            report($e);
        }

        $service->update([
            'status' => ServiceStatus::Terminated->value,
            'terminated_at' => now(),
        ]);
    }

    /**
     * Suspend services whose expiry is past (after the grace period).
     */
    public function suspendExpiredServices(): int
    {
        $grace = (int) config('kelvcmc.billing.suspend_grace_days', 3);
        $count = 0;

        Service::where('status', ServiceStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays($grace))
            ->get()
            ->each(function (Service $service) use (&$count) {
                $this->suspend($service, 'Non-payment');
                $count++;
            });

        return $count;
    }

    /**
     * Terminate services past the termination grace period.
     */
    public function terminateExpiredServices(): int
    {
        $grace = (int) config('kelvcmc.billing.terminate_grace_days', 14);
        $count = 0;

        Service::where('status', ServiceStatus::Suspended->value)
            ->whereNotNull('suspended_at')
            ->where('suspended_at', '<', now()->subDays($grace))
            ->get()
            ->each(function (Service $service) use (&$count) {
                $this->terminate($service);
                $count++;
            });

        return $count;
    }

    public function expiryFromCycle(string $cycle): \Illuminate\Support\Carbon
    {
        $months = match ($cycle) {
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };

        return now()->addMonths($months);
    }
}
