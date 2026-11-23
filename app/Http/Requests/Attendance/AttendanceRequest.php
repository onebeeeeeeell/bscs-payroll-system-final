<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
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
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('archived_at')],
            // Hindi pwede ang petsa sa hinaharap, at isang record lang bawat employee
            // bawat araw (ang soft-deleted ay hindi binibilang; ang restore ay may sariling check).
            'work_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->startOfYear()->toDateString(),
                'before_or_equal:today',
                Rule::unique('attendances', 'work_date')
                    ->where('employee_id', $this->input('employee_id'))
                    ->whereNull('deleted_at')
                    ->ignore($this->currentAttendanceId()),
            ],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_date.unique' => 'This employee already has an attendance record for that date.',
            'work_date.before_or_equal' => 'The work date cannot be in the future.',
        ];
    }

    // Sa update, ang record na ine-edit ay hindi dapat ituring na duplicate ng sarili niya.
    private function currentAttendanceId(): ?int
    {
        $attendance = $this->route('attendance');

        return $attendance instanceof Attendance ? $attendance->getKey() : null;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('time_in') || $validator->errors()->has('time_out')) {
                    return;
                }

                if ($this->input('time_out') <= $this->input('time_in')) {
                    $validator->errors()->add('time_out', 'Time out must be later than time in on the same day.');
                }
            },
        ];
    }
}