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

    public function test_attendance_rejects_work_dates_before_the_current_year(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('attendance.create'))
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->startOfYear()->subDay()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
            ]);

        $response->assertRedirect(route('attendance.create'));
        $response->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_accepts_december_work_dates_in_the_current_year(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->endOfYear()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
            ])
            ->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'work_date' => today()->endOfYear()->toDateString(),
        ]);
    }

    public function test_attendance_rejects_work_dates_after_the_current_year(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->from(route('attendance.create'))
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->endOfYear()->addDay()->toDateString(),
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

    public function test_attendance_stats_aggregate_records_employees_and_overtime(): void
    {
        $this->actingAs(User::factory()->create());

        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();

        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'overtime_hours' => 1.5,
        ]);
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => today()->subDay()->toDateString(),
            'overtime_hours' => 0,
        ]);
        Attendance::factory()->create([
            'employee_id' => $otherEmployee->id,
            'overtime_hours' => 2,
        ]);

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.records', 3)
                ->where('stats.employees', 2)
                ->where('stats.with_overtime', 2)
                ->where('stats.today', 2)
            );
    }
}
