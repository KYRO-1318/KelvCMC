<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_amount', 'max_discount', 'max_uses', 'used',
        'first_order_only', 'product_ids', 'cycles', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'first_order_only' => 'boolean',
        'product_ids' => 'array',
        'cycles' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValidFor(float $subtotal, ?string $cycle = null, ?User $user = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used >= $this->max_uses) {
            return false;
        }

        if ($this->min_amount !== null && $subtotal < $this->min_amount) {
            return false;
        }

        if ($this->cycles !== null && $cycle !== null && ! in_array($cycle, $this->cycles, true)) {
            return false;
        }

        if ($this->first_order_only && $user !== null && $user->orders()->exists()) {
            return false;
        }

        return true;
    }

    /** Returns the discount amount for a given subtotal. */
    public function discountFor(float $subtotal): float
    {
        if ($this->type === 'fixed') {
            return min($this->value, $subtotal);
        }

        $amount = $subtotal * ($this->value / 100);

        if ($this->max_discount !== null) {
            $amount = min($amount, (float) $this->max_discount);
        }

        return round($amount, 2);
    }
}
