<?php

namespace Database\Seeders\Concerns;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait SeedsPermissions
{
    /**
     * Cria permissões ({modulo}.{recurso}.{acao}) e atribui-as aos roles indicados.
     * O role admin recebe sempre todas (ver TenantDatabaseSeeder). Idempotente.
     *
     * @param  array<string, list<string>>  $permissions  permissão => roles
     */
    protected function seedPermissions(array $permissions): void
    {
        foreach ($permissions as $name => $roles) {
            $permission = Permission::findOrCreate($name, 'web');

            foreach ($roles as $role) {
                Role::findOrCreate($role, 'web')->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
