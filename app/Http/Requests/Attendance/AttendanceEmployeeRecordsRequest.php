<?php

namespace App\Http\Requests\Attendance;

use App\Http\Requests\Concerns\ValidatesInput;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceEmployeeRecordsRequest extends FormRequest
{
    use ValidatesInput;

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'date.date_equals' => 'Only today\'s date can be used.',
        ];
    }

    public function rules(): array
    {
        return [
            'date' => $this->presentDateRules(),
            'status' => ['nullable', 'in:active,deleted'],
        ];
    }
}