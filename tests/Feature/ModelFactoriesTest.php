<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_model_factories_create_schema_valid_related_records(): void
    {
        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->create();
        $payrollPeriod = PayrollPeriod::factory()->create();
        $payroll = Payroll::factory()->create();
        $payslip = Payslip::factory()->create();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $contributionType = ContributionType::factory()->create();
        $contributionRate = ContributionRate::factory()->create();
        $deduction = Deduction::factory()->create();

        $this->assertNotEmpty($employee->first_name);
        $this->assertNotEmpty($employee->last_name);
        $this->assertTrue($employee->position()->exists());
        $this->assertTrue($employee->department()->exists());
        $this->assertTrue($attendance->employee()->exists());
        $this->assertTrue($payroll->employee()->exists());
        $this->assertTrue($payroll->payrollPeriod()->exists());
        $this->assertTrue($payroll->user()->exists());
        $this->assertEqualsWithDelta(
            round((float) $payroll->gross_pay - (float) $payroll->total_deductions, 2),
            (float) $payroll->net_pay,
            0.001,
        );
        $this->assertTrue($payslip->payroll()->exists());
        $this->assertNotEmpty($department->department_name);
        $this->assertNotEmpty($position->title);
        $this->assertContains($contributionType->name, ['SSS', 'PhilHealth', 'Pag-IBIG']);
        $this->assertTrue($contributionRate->contributionType()->exists());
        $this->assertTrue($deduction->payroll()->exists());
        $this->assertTrue($deduction->contributionRate()->exists());
    }
}
