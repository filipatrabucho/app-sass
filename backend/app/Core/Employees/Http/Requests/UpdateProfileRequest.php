<?php

namespace App\Core\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Campos que o próprio colaborador pode alterar no portal.
 */
class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.country' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:34'],
            'locale' => ['sometimes', 'in:pt_PT,en'],
        ];
    }

    public function attributes(): array
    {
        return __('employees.attributes');
    }
}
