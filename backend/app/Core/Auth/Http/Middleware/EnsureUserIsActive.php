<?php

namespace App\Core\Auth\Http\Middleware;

use App\Core\Http\ApiError;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Termina a sessão de utilizadores desativados (o login já os recusa).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->active) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
            }

            return ApiError::response(__('auth.disabled'), 'UNAUTHENTICATED', 401);
        }

        return $next($request);
    }
}
