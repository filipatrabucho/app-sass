<?php

namespace App\Central\Http\Requests;

use App\Core\Licensing\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantModulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'modules' => ['required', 'array', 'min:1'],
            'modules.*.key' => ['required', 'string', 'distinct', Rule::exists(Module::class, 'key')],
            'modules.*.active' => ['required', 'boolean'],
            'modules.*.starts_at' => ['nullable', 'date'],
            'modules.*.ends_at' => ['nullable', 'date', 'after_or_equal:modules.*.starts_at'],
            'modules.*.max_users' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
