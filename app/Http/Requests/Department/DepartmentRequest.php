<?php

namespace App\Http\Requests\Department;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
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
            'department_name' => [
                $required,
                'string',
                'max:30',
                'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u',
                Rule::unique('departments', 'department_name')->ignore($this->route('department')),
            ],
        ];
    }
}
