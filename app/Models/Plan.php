<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'description', 'product_id',
        'price_monthly', 'price_quarterly', 'price_semi_annually', 'price_annually', 'setup_fee',
        'disk_mb', 'bandwidth_gb', 'cpu_cores', 'ram_mb', 'swap_mb', 'databases', 'email_accounts', 'domains',
        'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_semi_annually' => 'decimal:2',
        'price_annually' => 'decimal:2',
        'setup_fee' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public function specs(): array
    {
        return array_filter([
            'Disk' => $this->disk_mb ? $this->disk_mb.' MB' : null,
            'Bandwidth' => $this->bandwidth_gb ? $this->bandwidth_gb.' GB' : null,
            'CPU Cores' => $this->cpu_cores,
            'RAM' => $this->ram_mb ? $this->ram_mb.' MB' : null,
            'Databases' => $this->databases,
            'Email Accounts' => $this->email_accounts,
            'Domains' => $this->domains,
        ]);
    }
}
