<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsPermissions;
use Illuminate\Database\Seeder;

/**
 * Permissões do Core ({modulo}.{recurso}.{acao}). O admin tem todas (ver Gate::before).
 */
class CorePermissionsSeeder extends Seeder
{
    use SeedsPermissions;

    public function run(): void
    {
        $this->seedPermissions([
            'core.settings.manage' => [],
            'core.users.manage' => [],
            'core.audit.view' => [],
            'core.employees.view' => ['manager', 'hr'],
            'core.employees.manage' => ['hr'],
            'core.departments.manage' => ['hr'],
            'core.positions.manage' => ['hr'],
            'core.holidays.manage' => ['hr'],
        ]);
    }
}
