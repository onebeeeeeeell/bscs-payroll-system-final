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
     * Purpose: Sa create: payroll (isa lang na payslip bawat payroll) at petsa.
     * Sa update: petsa lang. Hindi na pwedeng ilipat ng payroll ang payslip.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'date_generated' => ['required', 'date'],
        ];

        if (! $this->isMethod('PATCH') && ! $this->isMethod('PUT')) {
            $rules['payroll_id'] = [
                'required',
                'integer',
                'exists:payrolls,id',
                Rule::unique('payslips', 'payroll_id'),
            ];
        }

        return $rules;
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