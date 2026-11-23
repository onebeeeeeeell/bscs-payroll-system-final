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
            // Eksaktong format ng datetime-local input (Y-m-dTH:i). Hindi pwede ang
            // "next monday" o kung anong loose string. Ang +1 araw ay tolerance lang
            // sa timezone ng browser vs server; hindi pwede ang petsa sa hinaharap.
            'date_generated' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'before_or_equal:'.now()->addDay()->toDateTimeString(),
            ],
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
            'date_generated.date_format' => 'The date generated must be a valid date and time.',
            'date_generated.before_or_equal' => 'The date generated cannot be in the future.',
        ];
    }
}