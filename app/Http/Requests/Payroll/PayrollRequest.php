<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Rules
     * Purpose: Update lang ang gumagamit nito (walang create/store route; ang
     * payroll ay ginagawa ng Process payroll). Tatlong field lang ang puwedeng
     * baguhin: gross_pay, total_deductions, date_processed. Ang net_pay,
     * employee, period, at user_id ay hindi validated dito kaya wala sa
     * validated(): net_pay ay kino-compute ng PayrollService, at ang tatlo pa
     * ay hindi na nagagalaw pagkatapos ma-process.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gross_pay' => ['required', 'numeric', 'min:0'],
            'total_deductions' => ['required', 'numeric', 'min:0'],
            'date_processed' => ['required', 'date'],
        ];
    }
}