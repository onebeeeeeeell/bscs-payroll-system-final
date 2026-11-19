<?php

namespace App\Http\Requests\Payslip;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayslipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Rules
     * Purpose: Create lang ang gumagamit nito (walang edit/update na ng payslip):
     * payroll (isa lang na payslip bawat payroll) at petsa.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_generated' => ['required', 'date'],
            'payroll_id' => [
                'required',
                'integer',
                'exists:payrolls,id',
                Rule::unique('payslips', 'payroll_id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payroll_id.unique' => 'A payslip was already generated for this payroll.',
        ];
    }
}