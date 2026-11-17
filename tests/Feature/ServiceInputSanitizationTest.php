<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\DepartmentService;
use App\Services\EmploymentStatusService;
use App\Services\PayrollPeriodService;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceInputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_service_normalizes_text_and_discards_unsupported_fields(): void
    {
        $department = app(DepartmentService::class)->create([
            'department_name' => ' <b>Human</b> Resources ',
            'id' => 999,
            'employees_count' => 100,
        ]);

        $this->assertSame('Human Resources', $department->department_name);
        $this->assertNotSame(999, $department->id);
        $this->assertArrayNotHasKey('employees_count', $department->getAttributes());
    }

    public function test_other_text_services_strip_tags_and_trim_values(): void
    {
        $position = app(PositionService::class)->create([
            'title' => ' <b>Lead</b> Operator ',
        ]);
        $employmentStatus = app(EmploymentStatusService::class)->create([
            'name' => ' <em>Seasonal</em> ',
        ]);
        $payrollPeriod = app(PayrollPeriodService::class)->create([
            'start_date' => ' 2026-01-01 ',
            'end_date' => ' 2026-01-15 ',
            'pay_date' => ' 2026-01-20 ',
            'status' => ' <b>Pending</b> ',
        ]);

        $this->assertSame('Lead Operator', $position->title);
        $this->assertSame('Seasonal', $employmentStatus->name);
        $this->assertSame('Pending', $payrollPeriod->status);
        $this->assertSame('2026-01-01', $payrollPeriod->start_date);
    }

    public function test_attendance_service_discards_client_calculated_fields(): void
    {
        $employee = Employee::factory()->create();

        $attendance = app(AttendanceService::class)->create([
            'employee_id' => $employee->id,
            'work_date' => today()->toDateString(),
            'time_in' => '08:00',
            'time_out' => '17:00',
            'hours_worked' => 999,
            'overtime_hours' => 999,
        ]);

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertSame('9.00', $attendance->hours_worked);
        $this->assertSame('1.00', $attendance->overtime_hours);
    }
}
