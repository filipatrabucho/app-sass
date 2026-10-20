<?php

namespace App\Core\Licensing\Jobs;

use App\Core\Licensing\LicenseService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Job middleware: descarta o job se o módulo não estiver licenciado para o tenant atual.
 *
 *   public function middleware(): array { return [new RequiresModuleLicense('vacations')]; }
 *
 * Também serve para listeners em fila (método middleware()).
 */
class RequiresModuleLicense
{
    public function __construct(private readonly string $moduleKey) {}

    public function handle(object $job, Closure $next): mixed
    {
        if (! app(LicenseService::class)->isActiveForCurrentTenant($this->moduleKey)) {
            Log::info('Job ignorado: módulo sem licença.', [
                'module' => $this->moduleKey,
                'tenant' => tenant()?->getTenantKey(),
                'job' => $job::class,
            ]);

            if (method_exists($job, 'delete')) {
                $job->delete();
            }

            return null;
        }

        return $next($job);
    }
}
