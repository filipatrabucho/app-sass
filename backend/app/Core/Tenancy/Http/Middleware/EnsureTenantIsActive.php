<?php

namespace App\Core\Tenancy\Http\Middleware;

use App\Core\Http\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant !== null && ! $tenant->isActive()) {
            return ApiError::response(__('tenancy.suspended'), 'TENANT_SUSPENDED', 403);
        }

        return $next($request);
    }
}
