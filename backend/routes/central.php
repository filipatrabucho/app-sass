<?php

use App\Central\Http\Controllers\CentralAuthController;
use App\Central\Http\Controllers\ModuleController;
use App\Central\Http\Controllers\TenantController;
use App\Central\Http\Controllers\TenantModuleController;
use Illuminate\Support\Facades\Route;

/*
| API do painel central (admin.nossaapp.pt/api/central/...). Guard "central".
| Registado só nos domínios centrais (ver bootstrap/app.php).
*/

Route::post('auth/login', [CentralAuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:central')->group(function () {
    Route::get('auth/me', [CentralAuthController::class, 'me']);
    Route::post('auth/logout', [CentralAuthController::class, 'logout']);

    Route::get('modules', [ModuleController::class, 'index']);

    Route::get('tenants', [TenantController::class, 'index']);
    Route::post('tenants', [TenantController::class, 'store']);
    Route::get('tenants/{tenant}', [TenantController::class, 'show']);
    Route::put('tenants/{tenant}', [TenantController::class, 'update']);
    Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend']);
    Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate']);

    Route::get('tenants/{tenant}/modules', [TenantModuleController::class, 'index']);
    Route::put('tenants/{tenant}/modules', [TenantModuleController::class, 'update']);
});
