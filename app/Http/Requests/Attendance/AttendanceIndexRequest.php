<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:active,deleted'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'department_id' => $validated['department_id'] ?? '',
            'position_id' => $validated['position_id'] ?? '',
            'date' => $validated['date'] ?? '',
            'status' => $validated['status'] ?? 'active',
        ];
    }
}
