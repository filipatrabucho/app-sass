<?php

namespace App\Core\Employees\Http\Controllers;

use App\Core\Employees\Http\Requests\EmployeeRequest;
use App\Core\Employees\Http\Resources\EmployeeLookupResource;
use App\Core\Employees\Http\Resources\EmployeeResource;
use App\Core\Employees\Models\Employee;
use App\Core\Employees\Services\EmployeeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EmployeeController
{
    public function __construct(private readonly EmployeeService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Employee::class);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'manager_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
            'sort' => ['nullable', 'in:name,-name,hire_date,-hire_date,employee_number,-employee_number'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $request->input('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $employees = $this->visibleTo($request)
            ->with(['department', 'position', 'manager', 'user'])
            ->withCount('subordinates')
            ->when($request->input('search'), fn (Builder $q, string $s) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")))
            ->when($request->input('department_id'), fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->input('manager_id'), fn ($q, $id) => $q->where('manager_id', $id))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($column === 'name',
                fn ($q) => $q->orderBy('first_name', $direction)->orderBy('last_name', $direction),
                fn ($q) => $q->orderBy($column, $direction))
            ->paginate($request->integer('per_page', 15));

        return EmployeeResource::collection($employees);
    }

    /**
     * Pesquisa leve para selects (EmployeeSelect).
     */
    public function lookup(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Employee::class);
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'ids' => ['nullable', 'array']]);

        $employees = Employee::query()
            ->active()
            ->with('department')
            ->when($request->input('search'), fn (Builder $q, string $s) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")))
            ->when($request->input('ids'), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return EmployeeLookupResource::collection($employees);
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        Gate::authorize('create', Employee::class);

        $employee = $this->service->create($request->validated());

        return $this->resource($employee)->response()->setStatusCode(201);
    }

    public function show(Request $request, Employee $employee): EmployeeResource
    {
        Gate::authorize('view', $employee);

        return $this->resource($employee);
    }

    public function update(EmployeeRequest $request, Employee $employee): EmployeeResource
    {
        Gate::authorize('update', $employee);

        return $this->resource($this->service->update($employee, $request->validated()));
    }

    public function destroy(Employee $employee): Response
    {
        Gate::authorize('delete', $employee);
        $this->service->delete($employee);

        return response()->noContent();
    }

    private function resource(Employee $employee): EmployeeResource
    {
        $employee->load(['department', 'position', 'manager', 'user'])->loadCount('subordinates');
        $user = request()->user();

        return (new EmployeeResource($employee))
            ->withSensitive($user->can('core.employees.manage') || $employee->user_id === $user->id);
    }

    /**
     * RH vê todos; chefias só a sua equipa (direta e indireta).
     */
    private function visibleTo(Request $request): Builder
    {
        $user = $request->user();
        $query = Employee::query();

        if ($user->can('core.employees.manage')) {
            return $query;
        }

        $self = $user->employee;

        return $query->whereIn('id', $self ? $self->subordinateIds() : []);
    }
}
