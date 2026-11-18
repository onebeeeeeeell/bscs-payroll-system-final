<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\EmployeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimeClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_time_clock_is_available_but_attendance_management_requires_authentication(): void
    {
        $this->get(route('time-clock.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('time-clock'));

        $this->get(route('attendance.index'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_can_time_in_and_time_out_only_once_per_day(): void
    {
        $employee = $this->employeeWithPin();
        $this->travelTo(today()->setTime(8, 0));

        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($employee),
            'pin' => '123456',
            'action' => 'time_in',
        ])->assertRedirect(route('time-clock.index'));

        $attendance = Attendance::query()->firstOrFail();
        $this->assertNull($attendance->time_out);

        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($employee),
            'pin' => '123456',
            'action' => 'time_in',
        ])->assertSessionHasErrors('action');

        $this->travelTo(today()->setTime(17, 30));
        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($employee),
            'pin' => '123456',
            'action' => 'time_out',
        ])->assertRedirect(route('time-clock.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'hours_worked' => '9.50',
            'overtime_hours' => '1.50',
        ]);

        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($employee),
            'pin' => '123456',
            'action' => 'time_out',
        ])->assertSessionHasErrors('action');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_invalid_employee_pin_does_not_create_attendance(): void
    {
        $employee = $this->employeeWithPin();

        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($employee),
            'pin' => '654321',
            'action' => 'time_in',
        ])->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_unmatched_employee_name_error_is_attached_to_the_name_field(): void
    {
        $this->employeeWithPin();

        $this->post(route('time-clock.store'), [
            'full_name' => 'John Doe',
            'pin' => '123456',
            'action' => 'time_in',
        ])->assertSessionHasErrors('full_name');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_service_sanitizes_the_full_name_before_matching(): void
    {
        $employee = $this->employeeWithPin();

        app(AttendanceService::class)->punch('  <b>Jane</b>   Doe ', '123456', 'time_in');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'work_date' => today()->toDateString(),
        ]);
    }

    public function test_full_name_rejects_non_letters_and_names_over_30_characters(): void
    {
        $employee = $this->employeeWithPin();

        foreach (['Jane D0e', 'Jane Doe!', str_repeat('A', 31)] as $fullName) {
            $this->post(route('time-clock.store'), [
                'full_name' => $fullName,
                'pin' => '123456',
                'action' => 'time_in',
            ])->assertSessionHasErrors('full_name');
        }

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_full_names_are_resolved_by_pin(): void
    {
        $firstEmployee = $this->employeeWithPin();
        $secondEmployee = Employee::factory()->create([
            'first_name' => $firstEmployee->first_name,
            'last_name' => $firstEmployee->last_name,
            'suffix' => $firstEmployee->suffix,
            'pin' => Hash::make('654321'),
        ]);

        $this->post(route('time-clock.store'), [
            'full_name' => $this->fullName($firstEmployee),
            'pin' => '654321',
            'action' => 'time_in',
        ])->assertRedirect(route('time-clock.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $secondEmployee->id,
        ]);
    }

    public function test_employee_service_hashes_and_hides_time_clock_pins(): void
    {
        $employee = Employee::factory()->create();

        app(EmployeeService::class)->update($employee, [
            'pin' => '789012',
        ]);

        $employee->refresh();

        $this->assertTrue(Hash::check('789012', $employee->pin));
        $this->assertArrayNotHasKey('pin', $employee->toArray());
    }

    private function employeeWithPin(): Employee
    {
        return Employee::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'pin' => Hash::make('123456'),
        ]);
    }

    private function fullName(Employee $employee): string
    {
        return implode(' ', array_filter([
            $employee->first_name,
            $employee->last_name,
            $employee->suffix,
        ]));
    }
}
