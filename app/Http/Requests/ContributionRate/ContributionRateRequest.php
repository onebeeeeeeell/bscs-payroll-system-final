<?php

namespace App\Http\Requests\ContributionRate;

use App\Http\Requests\Concerns\ValidatesInput;
use App\Models\ContributionRate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContributionRateRequest extends FormRequest
{
    use ValidatesInput;

    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Rules
     * Purpose: Sa create: type at rate. Sa update: rate lang. Hindi na pwedeng
     * ilipat ng type ang existing na rate (guguluhin nito ang history).
     * calculation_type: 'fixed' (flat PHP amount bawat payroll) o 'percent'
     * (porsyento ng gross pay, 0-100). Rate: numbers at decimal lang, hanggang
     * 2 decimal places (decimal 10,2).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'calculation_type' => ['required', Rule::in(ContributionRate::CALCULATION_TYPES)],
            'rate' => [
                ...$this->amountRules(),
                ...($this->input('calculation_type') === ContributionRate::TYPE_PERCENT ? ['max:100'] : []),
            ],
        ];

        if (! $this->isMethod('PATCH') && ! $this->isMethod('PUT')) {
            $rules['contribution_type_id'] = ['required', 'integer', 'exists:contribution_types,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rate.regex' => 'The rate must be a number with up to 2 decimal places.',
        ];
    }
}