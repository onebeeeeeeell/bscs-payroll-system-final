<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'hire_year' => ['nullable', 'integer', 'between:1900,9999'],
            'hire_month' => ['nullable', 'integer', 'between:1,12'],
            'status' => ['nullable', 'in:active,archived'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'department_id' => $validated['department_id'] ?? '',
            'position_id' => $validated['position_id'] ?? '',
            'hire_year' => $validated['hire_year'] ?? '',
            'hire_month' => $validated['hire_month'] ?? '',
            'status' => $validated['status'] ?? 'active',
        ];
    }
}
