<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Process payroll validation
     * Purpose: Isang request para sa preview (GET) at sa pag-save (POST).
     * - GET: payroll_period_id ay optional (walang napili pa) at may optional
     *   search sa employee table. 'string' ang humaharang sa ?search[]=x.
     * - POST: payroll_period_id ay required.
     * Hindi na ine-exists-check dito ang period at ang status nito: ang service
     * na ang nagloload ng period (isang query lang) at nagche-check kung
     * processable pa. Ang user_id at amounts ay hindi galing sa request, ang
     * server ang nagko-compute at kumukuha ng user sa auth.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $period = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'payroll_period_id' => [$period, 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}