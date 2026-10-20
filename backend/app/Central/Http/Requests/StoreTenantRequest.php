<?php

namespace App\Central\Http\Requests;

use App\Core\Licensing\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge(['subdomain' => strtolower(trim((string) $this->input('subdomain')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(['admin', 'www', 'api', 'app', 'mail']),
            ],
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255'],
            'admin.password' => ['required', 'string', Password::min(8)],
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['string', Rule::exists(Module::class, 'key')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('central.fields.name'),
            'subdomain' => __('central.fields.subdomain'),
            'admin.name' => __('central.fields.admin_name'),
            'admin.email' => __('central.fields.admin_email'),
            'admin.password' => __('central.fields.admin_password'),
        ];
    }
}
