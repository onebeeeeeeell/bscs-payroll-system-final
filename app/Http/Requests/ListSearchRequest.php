<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: ?search= validation
     * Purpose: Para sa mga index page na may search (departments, positions,
     * contribution types/rates, payrolls, payroll periods). 'string' ang
     * humaharang sa ?search[]=x, 'max:100' naman sa sobrang habang term.
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}