<?php

namespace App\Core\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defesa em profundidade: uma sessão criada num tenant nunca é aceite noutro.
 * (Os cookies já são host-only, mas não confiamos só nisso.)
 */
class EnsureSessionBelongsToTenant
{
    public const SESSION_KEY = 'tenant_id';

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenant()?->getTenantKey();

        if ($tenantId !== null && $request->hasSession()) {
            $session = $request->session();
            $owner = $session->get(self::SESSION_KEY);

            if ($owner !== null && $owner !== $tenantId) {
                $session->invalidate();
                // Esquece utilizadores já resolvidos pelos guards neste pedido.
                Auth::forgetGuards();
            }

            $session->put(self::SESSION_KEY, $tenantId);
        }

        return $next($request);
    }
}
