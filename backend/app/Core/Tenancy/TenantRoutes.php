<?php

namespace App\Core\Tenancy;

use App\Core\Tenancy\Http\Middleware\EnsureSessionBelongsToTenant;
use App\Core\Tenancy\Http\Middleware\EnsureTenantIsActive;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Stack de middleware comum a todas as rotas de API de tenant (core e módulos).
 */
final class TenantRoutes
{
    public static function apiMiddleware(): array
    {
        return [
            'api',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            EnsureTenantIsActive::class,
            EnsureSessionBelongsToTenant::class,
        ];
    }
}
