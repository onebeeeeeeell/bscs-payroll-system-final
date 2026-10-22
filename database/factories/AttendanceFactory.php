<?php
namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workDate = now()->toDateString();

        return [
            'employee_id' => Employee::factory()->state(['date_hired' => $workDate]),
            'work_date' => $workDate,
            'time_in' => '08:00',
            'time_out' => '17:00',
            'hours_worked' => 8,
            'overtime_hours' => 0,
        ];
    }
}