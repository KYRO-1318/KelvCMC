<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [];

        foreach (config('permissions.permissions', []) as $name => $label) {
            $permissions[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['label' => $label, 'guard_name' => 'web'],
            );
        }

        foreach (config('permissions.roles', []) as $name => $roleConfig) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $roleConfig['label'], 'guard_name' => 'web'],
            );

            if ($roleConfig['permissions'] === '*') {
                $role->permissions()->sync(collect($permissions)->pluck('id')->all());
            } else {
                $role->permissions()->sync(
                    collect($roleConfig['permissions'])->map(fn ($p) => $permissions[$p]->id)->all()
                );
            }
        }
    }
}
