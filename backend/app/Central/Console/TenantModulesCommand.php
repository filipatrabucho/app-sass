<?php

namespace App\Central\Console;

use App\Core\Http\ApiException;
use App\Core\Licensing\LicenseService;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Domain;

class TenantModulesCommand extends Command
{
    protected $signature = 'tenants:modules
        {tenant : ID, subdomínio ou domínio do tenant}
        {--enable=* : Módulos a ativar}
        {--disable=* : Módulos a desativar}';

    protected $description = 'Lista, ativa ou desativa módulos de um tenant';

    public function handle(LicenseService $licenses): int
    {
        $tenant = $this->resolveTenant($this->argument('tenant'));

        if ($tenant === null) {
            $this->components->error('Tenant não encontrado.');

            return self::FAILURE;
        }

        $changes = [
            ...array_map(fn ($key) => ['key' => $key, 'active' => true], $this->option('enable')),
            ...array_map(fn ($key) => ['key' => $key, 'active' => false], $this->option('disable')),
        ];

        if ($changes !== []) {
            try {
                $licenses->update($tenant, $changes);
            } catch (ApiException $e) {
                $this->components->error($e->getMessage());
                foreach ($e->errors as $messages) {
                    foreach ((array) $messages as $message) {
                        $this->line("  • {$message}");
                    }
                }

                return self::FAILURE;
            }
        }

        $this->components->info("Módulos ativos em {$tenant->name}:");
        foreach ($licenses->activeModules($tenant) as $key) {
            $this->line("  • {$key}");
        }

        return self::SUCCESS;
    }

    private function resolveTenant(string $value): ?Tenant
    {
        $domain = Domain::query()
            ->whereIn('domain', [$value, $value.'.'.config('platform.tenant_base_domain')])
            ->first();

        return Tenant::find($domain?->tenant_id ?? $value);
    }
}
