<?php

use App\Central\Console\CreateTenantCommand;
use App\Central\Console\TenantModulesCommand;
use App\Core\Http\ApiError;
use App\Core\Licensing\Http\Middleware\EnsureModuleLicensed;
use App\Core\Modules\Console\SyncModulesCommand;
use App\Core\Tenancy\Http\Middleware\EnsureSessionBelongsToTenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Painel central: só nos domínios centrais.
            foreach (config('tenancy.central_domains') as $domain) {
                Route::domain($domain)
                    ->prefix('api/central')
                    ->middleware('api')
                    ->group(base_path('routes/central.php'));
            }
            // As rotas de tenant são registadas no TenancyServiceProvider.
        },
    )
    ->withCommands([
        SyncModulesCommand::class,
        CreateTenantCommand::class,
        TenantModulesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'module' => EnsureModuleLicensed::class,
        ]);
        // A validação "sessão pertence a este tenant" tem de correr antes da autenticação.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnsureSessionBelongsToTenant::class,
        );
        // API pura: nunca redirecionar para uma página de login.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Formato de erro uniforme: { message, code, errors? }
        $exceptions->render(function (ValidationException $e, Request $request) {
            return ApiError::response($e->getMessage(), 'VALIDATION_ERROR', $e->status, $e->errors());
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiError::response(__('auth.unauthenticated'), 'UNAUTHENTICATED', 401);
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            return ApiError::response(__('auth.forbidden'), 'FORBIDDEN', 403);
        });
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e, Request $request) {
            return ApiError::response(__('tenancy.not_found'), 'TENANT_NOT_FOUND', 404);
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            return ApiError::response(__('errors.not_found'), 'NOT_FOUND', 404);
        });
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            return ApiError::response(__('errors.too_many_requests'), 'TOO_MANY_REQUESTS', 429);
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                return ApiError::response(__('errors.csrf_mismatch'), 'CSRF_TOKEN_MISMATCH', 419);
            }

            return null;
        });
    })->create();
