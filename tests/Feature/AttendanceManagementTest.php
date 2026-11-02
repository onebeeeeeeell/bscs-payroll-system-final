<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_calculates_total_worked_hours_and_overtime(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:30',
            ]);

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'hours_worked' => '9.50',
            'overtime_hours' => '1.50',
        ]);
    }

    public function test_attendance_rejects_time_out_not_later_than_time_in(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('attendance.create'))
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->toDateString(),
                'time_in' => '17:00',
                'time_out' => '08:00',
            ]);

        $response->assertRedirect(route('attendance.create'));
        $response->assertSessionHasErrors('time_out');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_rejects_work_dates_before_1900(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('attendance.create'))
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => '0005-05-05',
                'time_in' => '08:00',
                'time_out' => '17:00',
            ]);

        $response->assertRedirect(route('attendance.create'));
        $response->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_rejects_future_work_dates(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('attendance.create'))
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->addDay()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
            ]);

        $response->assertRedirect(route('attendance.create'));
        $response->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_update_recalculates_hours_and_overtime(): void
    {
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->put(route('attendance.update', $attendance), [
                'employee_id' => $attendance->employee_id,
                'work_date' => today()->toDateString(),
                'time_in' => '09:00',
                'time_out' => '16:30',
            ]);

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'hours_worked' => '7.50',
            'overtime_hours' => '0.00',
        ]);
    }
}
