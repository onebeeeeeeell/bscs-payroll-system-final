<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $stats = app(AttendanceService::class)->stats();
            $this->assertCount(1, DB::getQueryLog());
            $this->assertSame(2, $stats['today']);
        } finally {
            DB::disableQueryLog();
        }

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.records', 3)
                ->where('stats.employees', 2)
                ->where('stats.with_overtime', 2)
                ->where('stats.today', 2)
            );
    }

    public function test_archived_attendance_can_be_viewed_and_restored(): void
    {
        $attendance = Attendance::factory()->create();
        $attendance->delete();

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', [
                'employee' => $attendance->employee_id,
                'status' => 'deleted',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('attendances.data', 1));

        $this->actingAs(User::factory()->create())
            ->from(route('attendance.index', ['status' => 'deleted']))
            ->patch(route('attendance.restore', $attendance), [
                'status' => 'deleted',
                'department_id' => '',
                'position_id' => '',
                'date' => '',
            ])
            ->assertRedirect(route('attendance.index', ['status' => 'deleted']));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'deleted_at' => null,
        ]);
    }

    public function test_employee_records_page_includes_employee_position_and_department(): void
    {
        $employee = Employee::factory()->create();
        Attendance::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', $employee->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('attendance/employee-records')
                ->where('employee.id', $employee->id)
                ->where('employee.position.id', $employee->position_id)
                ->where('employee.department.id', $employee->department_id)
                ->has('employee.position.title')
                ->has('employee.department.department_name')
            );
    }

    public function test_employee_records_page_only_lists_that_employees_records(): void
    {
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();

        Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->toDateString()]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->subDay()->toDateString()]);
        Attendance::factory()->create(['employee_id' => $other->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', $employee->id))
            ->assertInertia(fn ($page) => $page
                ->has('attendances.data', 2)
                ->where('attendances.data.0.employee_id', $employee->id)
                ->where('stats.records', 2)
            );
    }

    public function test_employee_records_page_summarizes_hours_and_filters_by_date(): void
    {
        $employee = Employee::factory()->create();
        $today = today()->toDateString();

        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => $today,
            'hours_worked' => 9.5,
            'overtime_hours' => 1.5,
        ]);
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => today()->subDay()->toDateString(),
            'time_out' => null,
            'hours_worked' => 0,
            'overtime_hours' => 0,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', $employee->id))
            ->assertInertia(fn ($page) => $page
                ->where('stats.records', 2)
                ->where('stats.total_hours', 9.5)
                ->where('stats.total_overtime', 1.5)
                ->where('stats.open_records', 1)
            );

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', ['employee' => $employee->id, 'date' => $today]))
            ->assertInertia(fn ($page) => $page
                ->has('attendances.data', 1)
                ->where('filters.date', $today)
            );
    }

    public function test_employee_records_page_returns_404_for_unknown_employee(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('attendance.employee-records', 999999))
            ->assertNotFound();
    }

    public function test_attendance_edit_loads_active_employees_for_selection(): void
    {
        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('attendance.edit', $attendance))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('attendance/edit')
                ->has('employees', 1)
                ->where('employees.0.id', $employee->id)
            );
    }
}