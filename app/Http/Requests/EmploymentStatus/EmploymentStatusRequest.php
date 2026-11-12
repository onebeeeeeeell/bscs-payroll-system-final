<?php

namespace App\Http\Requests\EmploymentStatus;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmploymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'name' => [
                $required,
                'string',
                'max:30',
                'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u',
                Rule::unique('employment_statuses', 'name')->ignore($this->route('status')),
            ],
        ];
    }
}
