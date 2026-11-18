<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TimeClockRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name');

        if (is_string($fullName)) {
            $this->merge([
                'full_name' => preg_replace('/\s+/u', ' ', trim($fullName)),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:30', 'regex:/^[\p{L}]+(?:\s+[\p{L}]+)*$/u'],
            'pin' => ['required', 'digits:6'],
            'action' => ['required', 'in:time_in,time_out'],
        ];
    }
}
