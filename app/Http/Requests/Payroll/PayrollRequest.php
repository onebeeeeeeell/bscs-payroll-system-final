<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRequest extends FormRequest
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
            'employee_id' => [$required, 'integer', Rule::exists('employees', 'id')->whereNull('archived_at')],
            'payroll_period_id' => [$required, 'integer', 'exists:payroll_periods,id'],
            'user_id' => [$required, 'integer', 'exists:users,id'],
            'gross_pay' => [$required, 'numeric', 'min:0'],
            'total_deductions' => [$required, 'numeric', 'min:0'],
            'net_pay' => [$required, 'numeric', 'min:0'],
            'date_processed' => [$required, 'date'],
        ];
    }
}
