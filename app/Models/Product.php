<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'type', 'module',
        'price_monthly', 'price_quarterly', 'price_semi_annually', 'price_annually', 'setup_fee',
        'features', 'metadata', 'is_active', 'is_featured', 'is_recurring', 'stock', 'sort_order',
        'server_group_id',
    ];

    protected $casts = [
        'features' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_recurring' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_semi_annually' => 'decimal:2',
        'price_annually' => 'decimal:2',
        'setup_fee' => 'decimal:2',
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class)->orderBy('sort_order');
    }

    public function serverGroup(): BelongsTo
    {
        return $this->belongsTo(ServerGroup::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function priceFor(string $cycle): float
    {
        return (float) match ($cycle) {
            'quarterly' => $this->price_quarterly ?? $this->price_monthly * 3,
            'semi_annually' => $this->price_semi_annually ?? $this->price_monthly * 6,
            'annually' => $this->price_annually ?? $this->price_monthly * 12,
            'onetime' => $this->price_monthly,
            default => $this->price_monthly,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
