<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsPermissions;
use Illuminate\Database\Seeder;

/**
 * Permissões do Core. Crescem na Fase 2 (colaboradores, departamentos, feriados...).
 */
class CorePermissionsSeeder extends Seeder
{
    use SeedsPermissions;

    public function run(): void
    {
        $this->seedPermissions([
            'core.settings.manage' => [],
        ]);
    }
}
