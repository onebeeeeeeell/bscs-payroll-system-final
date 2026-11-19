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

    // Walang prepareForValidation dito: sanitization (squish, strip tags, null kung blank)
    // nasa EmployeeService na. Validation lang ang trabaho ng request.

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        $employee = $this->route('employee');
        $dateHiredRules = [$required, 'date'];

        if ($this->isMethod('POST')) {
            $dateHiredRules[] = 'after_or_equal:today';
        }

        $dateHiredRules[] = 'before_or_equal:'.now()->endOfYear()->toDateString();

        return [
            'first_name' => [$required, 'string', 'max:50', 'regex:/^[\p{L}\p{M}.\'-]+(?:\s+[\p{L}\p{M}.\'-]+)*$/u'],
            'last_name' => [$required, 'string', 'max:50', 'regex:/^[\p{L}\p{M}.\'-]+(?:\s+[\p{L}\p{M}.\'-]+)*$/u'],
            'suffix' => ['nullable', 'string', 'max:5', 'regex:/^[\p{L}\p{M}.\'-]+(?:\s+[\p{L}\p{M}.\'-]+)*$/u'],
            'position_id' => [$required, 'integer', 'exists:positions,id'],
            'department_id' => [$required, 'integer', 'exists:departments,id'],
            'date_hired' => $dateHiredRules,
            'daily_rate' => [$required, 'numeric', 'regex:/^\d+(?:\.\d{1,2})?$/', 'min:100', 'max:550'],
            'sss_no' => ['nullable', 'digits:10', Rule::unique('employees', 'sss_no')->ignore($employee)],
            'philhealth_no' => ['nullable', 'digits:12', Rule::unique('employees', 'philhealth_no')->ignore($employee)],
            'pagibig_no' => ['nullable', 'digits:12', Rule::unique('employees', 'pagibig_no')->ignore($employee)],
            'tin_no' => ['nullable', 'digits_between:9,12', Rule::unique('employees', 'tin_no')->ignore($employee)],
            'pin' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'nullable', 'digits:6'],
            'status' => [$required, Rule::exists('employment_statuses', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daily_rate.min' => 'The daily rate must be at least 100.',
            'daily_rate.max' => 'The daily rate cannot exceed 550.',
            'daily_rate.regex' => 'The daily rate must contain only numbers and an optional decimal point with up to two decimal places.',
            'date_hired.after_or_equal' => 'The hire date must be today or a future date.',
            'date_hired.before_or_equal' => 'The hire date must be within the current year.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'suffix.regex' => 'The suffix may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'sss_no.digits' => 'The SSS number must be 10 digits.',
            'philhealth_no.digits' => 'The PhilHealth number must be 12 digits.',
            'pagibig_no.digits' => 'The Pag-IBIG number must be 12 digits.',
            'tin_no.digits_between' => 'The TIN must be 9 to 12 digits.',
        ];
    }
}