<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'user_id', 'order_id', 'service_id', 'status',
        'subtotal', 'discount', 'tax_rate', 'tax_amount', 'total', 'currency',
        'coupon_id', 'metadata', 'due_at', 'paid_at', 'reminded_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, ['open', 'overdue']);
    }

    public function scopePayable(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'overdue']);
    }

    public static function generateNumber(): string
    {
        $prefix = config('kelvcmc.billing.prefix', 'INV');

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) (self::count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
