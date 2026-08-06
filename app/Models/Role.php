<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'guard_name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function givePermissionTo(string|array|Permission $permissions): static
    {
        $permissions = collect(is_array($permissions) ? $permissions : [$permissions])
            ->map(fn ($p) => $p instanceof Permission ? $p->id : Permission::firstOrCreate(['name' => $p])->id)
            ->all();

        $this->permissions()->syncWithoutDetaching($permissions);

        return $this;
    }

    public function syncPermissions(array $permissionIds): static
    {
        $this->permissions()->sync($permissionIds);

        return $this;
    }
}
