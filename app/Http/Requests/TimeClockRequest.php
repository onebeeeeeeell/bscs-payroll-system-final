<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TimeClockRequest extends FormRequest
{
    // Walang prepareForValidation dito: ang pag-squish ng full_name ay nasa
    // AttendanceService na (sanitizeFullName). Validation lang ang trabaho ng request.

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