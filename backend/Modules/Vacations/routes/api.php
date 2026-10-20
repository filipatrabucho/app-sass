<?php

use Illuminate\Support\Facades\Route;
use Modules\Vacations\Http\Controllers\MyVacationsController;

/*
| Rotas do módulo Férias. O ModuleServiceProvider aplica automaticamente:
| prefixo /api, stack de tenant, auth:sanctum e module:vacations.
*/

Route::get('vacations/my', [MyVacationsController::class, 'index']);
