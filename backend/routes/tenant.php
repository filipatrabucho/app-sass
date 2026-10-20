<?php

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Auth\Http\Controllers\MeController;
use App\Core\Tenancy\TenantRoutes;
use Illuminate\Support\Facades\Route;

/*
| Rotas de API do tenant (empresa.nossaapp.pt/api/...).
| As rotas dos módulos são registadas por cada módulo (ver ModuleServiceProvider).
*/

Route::prefix('api')->middleware(TenantRoutes::apiMiddleware())->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [MeController::class, 'show']);
    });
});
