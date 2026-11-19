<?php

namespace App\Http\Requests\ContributionRate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Ang rate ay flat na halaga bawat payroll (decimal 10,2).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'rate' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];

        if (! $this->isMethod('PATCH') && ! $this->isMethod('PUT')) {
            $rules['contribution_type_id'] = ['required', 'integer', 'exists:contribution_types,id'];
        }

        return $rules;
    }
}