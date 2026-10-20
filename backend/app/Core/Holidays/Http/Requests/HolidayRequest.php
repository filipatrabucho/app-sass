<?php

namespace App\Core\Holidays\Http\Requests;

use App\Core\Holidays\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->route('holiday') ? 'sometimes' : 'required';

        return [
            'date' => [$required, 'date'],
            'name' => [$required, 'string', 'max:150'],
            'type' => [$required, Rule::in(Holiday::TYPES)],
            'region' => ['nullable', 'string', 'max:100', 'required_if:type,municipal'],
            'recurring' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return __('holidays.attributes');
    }
}
