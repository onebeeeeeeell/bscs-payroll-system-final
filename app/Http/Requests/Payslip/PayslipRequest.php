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
     * payroll lang (isa lang na payslip bawat payroll). Walang petsa galing sa user:
     * ang date_generated ay ang oras ng pag-generate (PayslipService::create).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Active (hindi naka-archive) na payroll lang, isang payslip bawat payroll.
            'payroll_id' => [
                'required',
                'integer',
                Rule::exists('payrolls', 'id')->whereNull('archived_at'),
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
            'payroll_id.exists' => 'The selected payroll is not available (it may be archived).',
            'payroll_id.unique' => 'A payslip was already generated for this payroll.',
        ];
    }
}