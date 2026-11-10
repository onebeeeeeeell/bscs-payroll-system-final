<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // trim spaces on the name fields.
        foreach (['first_name', 'last_name', 'suffix'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([
                    // trim the ends and make multiple spaces into one
                    $field => preg_replace('/\s+/u', ' ', trim($value)),
                ]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        $employee = $this->route('employee');

        return [
            'first_name' => [$required, 'string', 'max:50', 'regex:/^[\p{L}]+(?:\s+[\p{L}]+)*$/u'],
            'last_name' => [$required, 'string', 'max:20', 'regex:/^[\p{L}]+(?:\s+[\p{L}]+)*$/u'],
            'suffix' => ['nullable', 'string', 'max:3', 'regex:/^[\p{L}]+(?:\s+[\p{L}]+)*$/u'],
            'position_id' => [$required, 'integer', 'exists:positions,id'],
            'department_id' => [$required, 'integer', 'exists:departments,id'],
            'date_hired' => [$required, 'date', 'after_or_equal:today', 'before_or_equal:'.now()->endOfYear()->toDateString()],
            // max matches the decimal(10,2) column so it can't overflow
            'daily_rate' => [$required, 'numeric', 'regex:/^\d+(?:\.\d{1,2})?$/', 'min:0', 'max:99999999.99'],
            'sss_no' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'sss_no')->ignore($employee)],
            'philhealth_no' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'philhealth_no')->ignore($employee)],
            'pagibig_no' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'pagibig_no')->ignore($employee)],
            'tin_no' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'tin_no')->ignore($employee)],
            'status' => [$required, 'string', 'max:50', Rule::exists('employment_statuses', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_rate.max' => 'The daily rate is too large.',
            'daily_rate.regex' => 'The daily rate must contain only numbers and an optional decimal point with up to two decimal places.',
            'date_hired.after_or_equal' => 'The hire date must be today or a future date.',
            'date_hired.before_or_equal' => 'The hire date must be within the current year.',
            'first_name.regex' => 'The first name may only contain letters and spaces.',
            'last_name.regex' => 'The last name may only contain letters and spaces.',
            'suffix.regex' => 'The suffix may only contain letters and spaces.',
        ];
    }
}
