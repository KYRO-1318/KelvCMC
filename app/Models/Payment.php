<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'invoice_id', 'user_id', 'gateway', 'transaction_id',
        'amount', 'currency', 'status', 'metadata', 'paid_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateReference(): string
    {
        return 'PAY-'.strtoupper(Str::random(10));
    }
}
