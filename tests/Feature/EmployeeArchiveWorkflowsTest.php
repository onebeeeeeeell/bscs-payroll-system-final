<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeArchiveWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_employees_are_hidden_from_attendance_and_cannot_be_assigned(): void
    {
        $this->actingAs(User::factory()->create());

        $activeAttendance = Attendance::factory()->create();
        $archivedAttendance = Attendance::factory()->create();
        $archivedEmployee = $archivedAttendance->employee;
        $archivedEmployee->update(['archived_at' => now()]);

        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('attendances.data', 1)
                ->where('attendances.data.0.id', $activeAttendance->id)
            );

        $this->get(route('attendance.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('employees', 1)
                ->where('employees.0.id', $activeAttendance->employee_id)
            );

        $this->postJson(route('attendance.store'), [
            'employee_id' => $archivedEmployee->id,
            'work_date' => now()->toDateString(),
            'time_in' => '08:00',
            'time_out' => '17:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_archived_employees_cannot_be_added_to_new_payroll_records(): void
    {
        $archivedEmployee = Employee::factory()->create();
        $archivedEmployee->update(['archived_at' => now()]);
        $payrollPeriod = PayrollPeriod::factory()->create();
        $user = User::factory()->create();

        $this->postJson(route('payrolls.store'), [
            'employee_id' => $archivedEmployee->id,
            'payroll_period_id' => $payrollPeriod->id,
            'user_id' => $user->id,
            'gross_pay' => 1000,
            'total_deductions' => 100,
            'net_pay' => 900,
            'date_processed' => now()->toDateTimeString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');

        $this->assertDatabaseMissing('payrolls', ['employee_id' => $archivedEmployee->id]);
    }

    public function test_existing_payroll_and_payslip_history_remains_accessible_after_archiving(): void
    {
        $payslip = Payslip::factory()->create();
        $employee = $payslip->payroll->employee;
        $employee->update(['archived_at' => now()]);

        $this->get(route('payrolls.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payrolls.0.employee.id', $employee->id)
            );

        $this->get(route('payslips.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payslips.0.payroll.employee.id', $employee->id)
            );
    }

    public function test_payroll_service_rejects_archived_employees(): void
    {
        $employee = Employee::factory()->create();
        $employee->update(['archived_at' => now()]);
        $payrollPeriod = PayrollPeriod::factory()->create();
        $user = User::factory()->create();

        try {
            app(PayrollService::class)->create([
                'employee_id' => $employee->id,
                'payroll_period_id' => $payrollPeriod->id,
                'user_id' => $user->id,
                'gross_pay' => 1000,
                'total_deductions' => 100,
                'net_pay' => 900,
                'date_processed' => now(),
            ]);
            $this->fail('Expected archived employee payroll creation to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employee_id', $exception->errors());
        }

        $this->assertDatabaseMissing('payrolls', ['employee_id' => $employee->id]);
    }
}
