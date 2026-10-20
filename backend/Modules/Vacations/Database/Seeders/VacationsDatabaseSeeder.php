<?php

namespace Modules\Vacations\Database\Seeders;

use Database\Seeders\Concerns\SeedsPermissions;
use Illuminate\Database\Seeder;

/**
 * Permissões do módulo Férias (corre em cada BD de tenant).
 */
class VacationsDatabaseSeeder extends Seeder
{
    use SeedsPermissions;

    public function run(): void
    {
        $this->seedPermissions([
            'vacations.requests.view_own' => ['employee', 'manager', 'hr'],
            'vacations.requests.create' => ['employee', 'manager', 'hr'],
            'vacations.requests.cancel' => ['employee', 'manager', 'hr'],
            'vacations.requests.view_team' => ['manager', 'hr'],
            'vacations.requests.approve' => ['manager', 'hr'],
            'vacations.requests.view_all' => ['hr'],
            'vacations.requests.approve_cancel' => ['hr'],
            'vacations.balances.manage' => ['hr'],
            'vacations.policies.manage' => ['hr'],
        ]);
    }
}
