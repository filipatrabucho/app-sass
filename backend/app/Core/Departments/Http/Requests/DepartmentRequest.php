<?php

namespace App\Core\Departments\Http\Requests;

use App\Core\Departments\Models\Department;
use App\Core\Employees\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DepartmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [$this->route('department') ? 'sometimes' : 'required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'manager_employee_id' => ['nullable', 'integer', Rule::exists(Employee::class, 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Department|null $department */
            $department = $this->route('department');
            $parentId = $this->input('parent_id');

            if ($department && $parentId && in_array((int) $parentId, $department->descendantIds(), true)) {
                $validator->errors()->add('parent_id', __('departments.parent_cycle'));
            }
        }];
    }

    public function attributes(): array
    {
        return __('departments.attributes');
    }
}
