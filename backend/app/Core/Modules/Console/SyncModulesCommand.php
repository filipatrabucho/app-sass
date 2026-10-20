<?php

namespace App\Core\Modules\Console;

use App\Core\Licensing\Models\Module;
use App\Core\Modules\ModuleRegistry;
use Illuminate\Console\Command;

/**
 * Sincroniza o catálogo central (tabela modules) com os module.json instalados.
 */
class SyncModulesCommand extends Command
{
    protected $signature = 'modules:sync';

    protected $description = 'Sincroniza o catálogo de módulos (BD central) com os module.json';

    public function handle(ModuleRegistry $registry): int
    {
        foreach ($registry->all() as $manifest) {
            Module::updateOrCreate(['key' => $manifest->key], [
                'name' => $manifest->name,
                'description' => $manifest->description,
                'version' => $manifest->version,
                'requires' => $manifest->requires,
            ]);
            $this->components->info("{$manifest->key} ({$manifest->version})");
        }

        return self::SUCCESS;
    }
}
