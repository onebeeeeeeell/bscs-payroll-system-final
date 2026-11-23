<?php

namespace App\Http\Requests\PayrollPeriod;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollPeriodRequest extends FormRequest
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
        $payDateRules = [$required, 'date', 'after_or_equal:today'];

        if ($this->has('end_date')) {
            $payDateRules[] = 'after_or_equal:end_date';
        }

        return [
            'start_date' => [$required, 'date', 'after_or_equal:today'],
            'end_date' => [$required, 'date', 'after_or_equal:today', 'after_or_equal:start_date'],
            'pay_date' => $payDateRules,
            'status' => [$required, Rule::in(['Pending', 'Processing', 'Paid', 'Failed'])],
        ];
    }
}
