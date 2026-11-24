<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesInput;
use Illuminate\Foundation\Http\FormRequest;

class ListSearchRequest extends FormRequest
{
    use ValidatesInput;

    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: ?search= validation
     * Purpose: Para sa mga index page na may search (departments, positions,
     * contribution types/rates, deductions). Letters, spaces at . ' - lang,
     * hanggang 30 characters (pareho ng search box sa frontend). 'string' ang humaharang
     * sa ?search[]=x.
     */
    public function rules(): array
    {
        return [
            'search' => $this->searchRules(),
        ];
    }
}