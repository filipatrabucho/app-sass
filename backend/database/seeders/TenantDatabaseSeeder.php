<?php

namespace Database\Seeders;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder base de cada BD de tenant: roles iniciais + permissões do core e dos módulos.
 * Corre na criação do tenant (pipeline) e pode ser repetido com `php artisan tenants:seed`.
 */
class TenantDatabaseSeeder extends Seeder
{
    public const ROLES = ['admin', 'hr', 'manager', 'employee'];

    public function run(ModuleRegistry $modules): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->call(CorePermissionsSeeder::class);

        // As permissões de todos os módulos instalados são criadas, licenciados ou não:
        // o acesso é decidido pela licença; assim reativar um módulo não exige seeds.
        foreach ($modules->all() as $module) {
            if ($module->seeder !== null) {
                $this->call($module->seeder);
            }
        }

        Role::findByName('admin', 'web')->syncPermissions(Permission::all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
