<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'server_group_id', 'name', 'hostname', 'ip_address', 'integration',
        'remote_id', 'credentials', 'status', 'location', 'load', 'metadata', 'last_checked_at',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected $casts = [
        'metadata' => 'array',
        'load' => 'decimal:2',
        'last_checked_at' => 'datetime',
        'credentials' => 'encrypted',
    ];

    public function serverGroup(): BelongsTo
    {
        return $this->belongsTo(ServerGroup::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }
}
