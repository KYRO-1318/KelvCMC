<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerGroup extends Model
{
    protected $fillable = ['name', 'integration', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
