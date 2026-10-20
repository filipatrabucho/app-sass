<?php

namespace App\Core\Departments\Http\Controllers;

use App\Core\Departments\Http\Requests\DepartmentRequest;
use App\Core\Departments\Http\Resources\DepartmentResource;
use App\Core\Departments\Models\Department;
use App\Core\Http\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Listagem acessível a qualquer utilizador autenticado (filtros, organigrama); gestão pelo RH.
 * Devolve a lista plana; o frontend monta a árvore com parent_id.
 */
class DepartmentController
{
    public function index(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(
            Department::query()->with('manager')->withCount('employees')->orderBy('name')->get()
        );
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        Gate::authorize('core.departments.manage');
        $department = Department::create($request->validated());

        return (new DepartmentResource($department->load('manager')->loadCount('employees')))->response()->setStatusCode(201);
    }

    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department->load('manager')->loadCount('employees'));
    }

    public function update(DepartmentRequest $request, Department $department): DepartmentResource
    {
        Gate::authorize('core.departments.manage');
        $department->update($request->validated());

        return new DepartmentResource($department->load('manager')->loadCount('employees'));
    }

    public function destroy(Department $department): Response
    {
        Gate::authorize('core.departments.manage');

        if ($department->employees()->exists() || $department->children()->exists()) {
            throw new ApiException(__('departments.not_empty'), 'DEPARTMENT_NOT_EMPTY', 422);
        }

        $department->delete();

        return response()->noContent();
    }
}
