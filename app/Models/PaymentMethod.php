<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id', 'gateway', 'type', 'label', 'token', 'details', 'is_default', 'last_used_at',
    ];

    protected $casts = [
        'details' => 'array',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
        'token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
