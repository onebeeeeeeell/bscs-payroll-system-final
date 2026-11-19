<?php

namespace App\Http\Requests\ContributionRate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ContributionRate;

class ContributionRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Rules
     * Purpose: Sa create: type at rate. Sa update: rate lang. Hindi na pwedeng
     * ilipat ng type ang existing na rate (guguluhin nito ang history).
     * calculation_type: 'fixed' (flat PHP amount bawat payroll) o 'percent'
     * (porsyento ng gross pay, 0-100). Decimal 10,2 ang rate.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPercent = $this->input('calculation_type') === ContributionRate::TYPE_PERCENT;

        $rules = [
            'calculation_type' => ['required', Rule::in(ContributionRate::CALCULATION_TYPES)],
            'rate' => ['required', 'numeric', 'min:0', $isPercent ? 'max:100' : 'max:99999999.99'],
        ];

        if (! $this->isMethod('PATCH') && ! $this->isMethod('PUT')) {
            $rules['contribution_type_id'] = ['required', 'integer', 'exists:contribution_types,id'];
        }

        return $rules;
    }
}