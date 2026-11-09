<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_and_restoring_an_employee_preserves_payroll_history(): void
    {
        $payroll = Payroll::factory()->create();
        $employee = $payroll->employee;
        $payslip = Payslip::factory()->create(['payroll_id' => $payroll->id]);

        $this->delete(route('employees.destroy', $employee), ['status' => 'active'])
            ->assertRedirect(route('employees.index', ['status' => 'active']))
            ->assertSessionHas('success', 'Employee archived successfully.');

        $this->assertNotNull($employee->fresh()->archived_at);
        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertSame($employee->id, $payroll->fresh()->employee->id);
        $this->assertDatabaseHas('payslips', [
            'id' => $payslip->id,
            'payroll_id' => $payroll->id,
        ]);

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('employees.data', 0));

        $this->get(route('employees.index', ['status' => 'archived']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', 'archived')
                ->has('employees.data', 1)
                ->where('employees.data.0.id', $employee->id)
            );

        $this->patch(route('employees.restore', $employee), ['status' => 'archived'])
            ->assertRedirect(route('employees.index', ['status' => 'archived']))
            ->assertSessionHas('success', 'Employee restored successfully.');

        $this->assertNull($employee->fresh()->archived_at);
        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id, 'employee_id' => $employee->id]);
        $this->assertDatabaseHas('payslips', ['id' => $payslip->id, 'payroll_id' => $payroll->id]);
        $this->get(route('employees.index'))->assertInertia(fn ($page) => $page->has('employees.data', 1));
        $this->get(route('employees.index', ['status' => 'archived']))->assertInertia(fn ($page) => $page->has('employees.data', 0));
    }

    public function test_archived_employee_keeps_their_department_and_position_in_use(): void
    {
        $employee = Employee::factory()->create();
        $employee->update(['archived_at' => now()]);

        $this->deleteJson(route('employee-options.departments.destroy', $employee->department))
            ->assertStatus(409);
        $this->deleteJson(route('employee-options.positions.destroy', $employee->position))
            ->assertStatus(409);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'archived_at' => $employee->archived_at,
        ]);
    }

    public function test_other_employee_filters_also_apply_in_the_archived_view(): void
    {
        $employee = Employee::factory()->create([
            'date_hired' => '2024-05-15',
        ]);
        $employee->update(['archived_at' => now()]);
        Employee::factory()->create([
            'date_hired' => '2024-06-15',
        ]);

        $this->get(route('employees.index', [
            'status' => 'archived',
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'hire_year' => 2024,
            'hire_month' => 5,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', 'archived')
                ->has('employees.data', 1)
                ->where('employees.data.0.id', $employee->id)
            );
    }
}
