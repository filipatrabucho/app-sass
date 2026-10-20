<?php

namespace App\Core\Employees\Http\Requests;

use App\Core\Departments\Models\Department;
use App\Core\Departments\Models\Position;
use App\Core\Employees\Models\Employee;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');
        $required = $employee ? 'sometimes' : 'required';

        return [
            'first_name' => [$required, 'string', 'max:100'],
            'last_name' => [$required, 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(Employee::class, 'email')->ignore($employee?->id)->withoutTrashed()],
            'employee_number' => ['nullable', 'string', 'max:30', Rule::unique(Employee::class, 'employee_number')->ignore($employee?->id)],
            'nif' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.country' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:34'],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'position_id' => ['nullable', 'integer', Rule::exists(Position::class, 'id')],
            'manager_id' => ['nullable', 'integer', Rule::exists(Employee::class, 'id')->whereNull('deleted_at')],
            'hire_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in([Employee::STATUS_ACTIVE, Employee::STATUS_INACTIVE])],
            'create_account' => ['sometimes', 'boolean'],
            'role' => ['nullable', Rule::in($this->assignableRoles())],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Só o admin pode atribuir o role admin.
     *
     * @return list<string>
     */
    private function assignableRoles(): array
    {
        $roles = TenantDatabaseSeeder::ROLES;

        return $this->user()->can('core.users.manage') ? $roles : array_values(array_diff($roles, ['admin']));
    }

    public function attributes(): array
    {
        return __('employees.attributes');
    }
}
