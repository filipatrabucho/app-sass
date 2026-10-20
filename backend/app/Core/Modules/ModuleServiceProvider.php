<?php

namespace App\Core\Modules;

use App\Core\Tenancy\TenantRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base para os service providers dos módulos.
 *
 * Regista automaticamente:
 *  - routes/api.php sob /api, com o stack de tenant e o middleware module:{key};
 *  - Database/Migrations como migrations de tenant;
 *  - lang/ com o namespace {key}.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function moduleKey(): string;

    protected function modulePath(string $path = ''): string
    {
        $base = $this->app->make(ModuleRegistry::class)->find($this->moduleKey())->path;

        return $path === '' ? $base : $base.'/'.$path;
    }

    public function register(): void
    {
        $migrations = $this->modulePath('Database/Migrations');

        if (is_dir($migrations)) {
            config(['tenancy.migration_parameters.--path' => array_values(array_unique([
                ...config('tenancy.migration_parameters.--path', []),
                $migrations,
            ]))]);
        }
    }

    public function boot(): void
    {
        $routes = $this->modulePath('routes/api.php');

        if (is_file($routes) && ! $this->app->routesAreCached()) {
            Route::prefix('api')
                ->middleware([...TenantRoutes::apiMiddleware(), 'auth:sanctum', 'module:'.$this->moduleKey()])
                ->group($routes);
        }

        if (is_dir($lang = $this->modulePath('lang'))) {
            $this->loadTranslationsFrom($lang, $this->moduleKey());
            $this->loadJsonTranslationsFrom($lang);
        }
    }
}
