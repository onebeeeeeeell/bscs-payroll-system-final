<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesInput;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    use ValidatesInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // iisang rule sa lahat ng search box (30 characters, letters at . ' - lang)
            'q' => $this->searchRules(),
        ];
    }
}