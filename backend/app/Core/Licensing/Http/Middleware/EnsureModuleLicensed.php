<?php

namespace App\Core\Licensing\Http\Middleware;

use App\Core\Licensing\Exceptions\ModuleNotLicensedException;
use App\Core\Licensing\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso: ->middleware('module:vacations'). Aplicado automaticamente a todas as rotas de um módulo.
 */
class EnsureModuleLicensed
{
    public function __construct(private readonly LicenseService $licenses) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        if (! $this->licenses->isActiveForCurrentTenant($moduleKey)) {
            throw new ModuleNotLicensedException($moduleKey);
        }

        return $next($request);
    }
}
