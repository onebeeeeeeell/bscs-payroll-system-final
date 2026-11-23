<?php

namespace App\Http\Requests\Payroll;

use App\Http\Requests\Concerns\ValidatesInput;
use App\Models\Payroll;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    use ValidatesInput;

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
     * - Amounts: numbers at decimal lang, hanggang 2 decimal places.
     * - date_processed: ngayong araw lang (Y-m-d).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gross_pay' => [...$this->amountRules(), 'max:'.Payroll::MAX_GROSS_PAY],
            'total_deductions' => $this->amountRules(),
            'date_processed' => $this->presentDateRules(required: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gross_pay.regex' => 'Gross pay must be a number with up to 2 decimal places.',
            'gross_pay.max' => 'Gross pay cannot exceed 30,000.00.',
            'total_deductions.regex' => 'Total deductions must be a number with up to 2 decimal places.',
            'date_processed.date_equals' => 'Date processed must be today.',
            'date_processed.date_format' => 'Date processed must be today.',
        ];
    }
}