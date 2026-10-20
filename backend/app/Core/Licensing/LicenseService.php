<?php

namespace App\Core\Licensing;

use App\Core\Licensing\Exceptions\LicenseDependencyException;
use App\Core\Licensing\Models\TenantModule;
use App\Core\Modules\ModuleRegistry;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Leitura e gestão das licenças de módulos por tenant.
 *
 * As licenças vivem na BD central e são lidas com cache. Usamos Cache::store()
 * diretamente para que a chave seja a mesma em contexto central e de tenant
 * (o CacheTenancyBootstrapper só etiqueta chamadas feitas via fachada Cache::get()).
 */
class LicenseService
{
    public function __construct(private readonly ModuleRegistry $registry) {}

    /**
     * Chaves dos módulos efetivamente ativos (licença ativa, dentro das datas e módulo instalado).
     *
     * @return list<string>
     */
    public function activeModules(Tenant|string $tenant): array
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getTenantKey() : $tenant;

        return collect($this->licenses($tenantId))
            ->filter(fn (TenantModule $license) => $license->isEffective() && $this->registry->has($license->module_key))
            ->pluck('module_key')
            ->sort()
            ->values()
            ->all();
    }

    public function isActive(Tenant|string $tenant, string $moduleKey): bool
    {
        return in_array($moduleKey, $this->activeModules($tenant), true);
    }

    /**
     * Licença ativa no tenant atual (contexto de tenant). Falso fora de contexto de tenant.
     */
    public function isActiveForCurrentTenant(string $moduleKey): bool
    {
        $tenant = tenant();

        return $tenant !== null && $this->isActive($tenant, $moduleKey);
    }

    public function forget(Tenant|string $tenant): void
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getTenantKey() : $tenant;

        $this->cache()->forget($this->cacheKey($tenantId));
    }

    /**
     * Atualiza as licenças de um tenant. Os módulos não indicados ficam como estão.
     * Desativar nunca apaga dados: só muda o estado da licença.
     *
     * @param  list<array{key: string, active: bool, starts_at?: ?string, ends_at?: ?string, max_users?: ?int}>  $changes
     * @return Collection<int, TenantModule>
     */
    public function update(Tenant $tenant, array $changes): Collection
    {
        $current = $tenant->modules()->get()->keyBy('module_key');

        $resulting = $current->map(fn (TenantModule $m) => $m->active)->all();
        foreach ($changes as $change) {
            $resulting[$change['key']] = (bool) $change['active'];
        }

        $this->assertDependencies(array_keys(array_filter($resulting)));

        DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($tenant, $changes) {
            foreach ($changes as $change) {
                TenantModule::updateOrCreate(
                    ['tenant_id' => $tenant->getTenantKey(), 'module_key' => $change['key']],
                    [
                        'active' => (bool) $change['active'],
                        ...Arr::only($change, ['starts_at', 'ends_at', 'max_users']),
                    ],
                );
            }
        });

        $this->forget($tenant);

        return $tenant->modules()->get();
    }

    /**
     * Garante que todos os módulos ativos têm as dependências (requires) também ativas.
     *
     * @param  list<string>  $activeKeys
     */
    public function assertDependencies(array $activeKeys): void
    {
        $errors = [];

        foreach ($activeKeys as $key) {
            $manifest = $this->registry->find($key);

            if ($manifest === null) {
                $errors[$key][] = __('licensing.unknown_module', ['module' => $key]);

                continue;
            }

            $missing = array_diff($manifest->requires, $activeKeys);
            if ($missing !== []) {
                $errors[$key][] = __('licensing.missing_dependencies', [
                    'module' => $manifest->name,
                    'requires' => implode(', ', $missing),
                ]);
            }
        }

        if ($errors !== []) {
            throw new LicenseDependencyException(__('licensing.dependencies_invalid'), $errors);
        }
    }

    /**
     * @return Collection<int, TenantModule>
     */
    private function licenses(string $tenantId): Collection
    {
        $rows = $this->cache()->remember(
            $this->cacheKey($tenantId),
            config('platform.licenses_cache_ttl'),
            fn () => TenantModule::query()->where('tenant_id', $tenantId)->get()->map->getAttributes()->all(),
        );

        return collect($rows)->map(fn (array $row) => (new TenantModule)->setRawAttributes($row, true));
    }

    private function cacheKey(string $tenantId): string
    {
        return "licenses:tenant:{$tenantId}";
    }

    private function cache(): Repository
    {
        return Cache::store();
    }
}
