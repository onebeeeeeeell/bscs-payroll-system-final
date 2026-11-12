<?php

namespace App\Http\Requests\Position;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
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

        return [
            'title' => [
                $required,
                'string',
                'max:30',
                'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u',
                Rule::unique('positions', 'title')->ignore($this->route('position')),
            ],
        ];
    }
}
