<?php

use App\Core\Audit\Http\Controllers\AuditLogController;
use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Auth\Http\Controllers\MeController;
use App\Core\Auth\Http\Controllers\ProfileController;
use App\Core\Departments\Http\Controllers\DepartmentController;
use App\Core\Departments\Http\Controllers\PositionController;
use App\Core\Employees\Http\Controllers\AvatarController;
use App\Core\Employees\Http\Controllers\EmployeeAttachmentController;
use App\Core\Employees\Http\Controllers\EmployeeController;
use App\Core\Holidays\Http\Controllers\HolidayController;
use App\Core\Notifications\Http\Controllers\NotificationController;
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

    Route::middleware(TenantRoutes::authMiddleware())->group(function () {
        // Próprio utilizador
        Route::get('me', [MeController::class, 'show']);
        Route::put('me/profile', [ProfileController::class, 'update']);
        Route::post('me/avatar', [ProfileController::class, 'avatar']);

        // Colaboradores
        Route::get('employees/lookup', [EmployeeController::class, 'lookup']);
        Route::get('employees/{employee}/avatar', [AvatarController::class, 'show']);
        Route::get('employees/{employee}/attachments', [EmployeeAttachmentController::class, 'index']);
        Route::post('employees/{employee}/attachments', [EmployeeAttachmentController::class, 'store']);
        Route::get('employees/{employee}/attachments/{attachment}', [EmployeeAttachmentController::class, 'download']);
        Route::delete('employees/{employee}/attachments/{attachment}', [EmployeeAttachmentController::class, 'destroy']);
        Route::apiResource('employees', EmployeeController::class);

        // Estrutura
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('positions', PositionController::class)->except('show');

        // Feriados
        Route::post('holidays/import-national', [HolidayController::class, 'importNational']);
        Route::apiResource('holidays', HolidayController::class)->except('show');

        // Notificações
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'read']);

        // Auditoria
        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });
});
