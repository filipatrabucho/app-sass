<?php

namespace App\Providers;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Descobre os módulos (Modules/* /module.json) e regista os respetivos service providers.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry(base_path('Modules')));

        foreach ($this->app->make(ModuleRegistry::class)->all() as $module) {
            if ($module->provider !== null) {
                $this->app->register($module->provider);
            }
        }
    }
}
