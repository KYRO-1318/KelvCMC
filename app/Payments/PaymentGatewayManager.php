<?php

namespace App\Payments;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Collection;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $instances = [];

    /**
     * @param  array<string, class-string<PaymentGateway>>  $gateways
     */
    public function __construct(array $gateways)
    {
        foreach ($gateways as $id => $class) {
            $this->instances[$id] = app($class);
        }
    }

    public function get(string $id): ?PaymentGateway
    {
        return $this->instances[$id] ?? null;
    }

    /** @return Collection<int, PaymentGateway> */
    public function all(): Collection
    {
        return collect($this->instances)->values();
    }

    /** @return Collection<int, PaymentGateway> */
    public function enabled(): Collection
    {
        return $this->all()->filter(fn (PaymentGateway $gateway) => $gateway->isEnabled());
    }

    /**
     * Gateways available for a user, with saved methods attached.
     *
     * @return Collection<int, array{gateway: PaymentGateway, methods: Collection<int, PaymentMethod>}>
     */
    public function forUser(User $user): Collection
    {
        $methods = $user->paymentMethods;

        return $this->enabled()->map(function (PaymentGateway $gateway) use ($methods) {
            return [
                'gateway' => $gateway,
                'methods' => $methods->where('gateway', $gateway->id()),
            ];
        });
    }
}
