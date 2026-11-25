<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceEmployeeRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:active,deleted'],
        ];
    }
}