<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayslipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipEmployeeRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Hindi kailangan ng built assets (npm run build) para sa mga page test dito
        $this->withoutVite();

        $this->actingAs(User::factory()->create());
    }

    private function payslipFor(Employee $employee, float $netPay, $generatedAt = null): Payslip
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'net_pay' => $netPay,
        ]);

        return Payslip::factory()->create([
            'payroll_id' => $payroll->id,
            'date_generated' => $generatedAt ?? now(),
        ]);
    }

    public function test_payslip_index_shows_one_row_per_employee_with_totals(): void
    {
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();

        $this->payslipFor($employee, 1000);
        $this->payslipFor($employee, 2500, now()->subDay());
        $this->payslipFor($otherEmployee, 700, now()->subDays(2));

        $this->get(route('payslips.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 2)
                ->where('payslips.total', 2)
                ->where('payslips.data.0.employee_id', $employee->id)
                ->where('payslips.data.0.employee.id', $employee->id)
                ->where('payslips.data.0.payslips_count', 2)
                ->where('payslips.data.0.total_net_pay', fn ($value) => (float) $value === 3500.0)
                ->where('payslips.data.1.employee_id', $otherEmployee->id)
            );
    }

    public function test_payslip_index_lists_employees_with_archived_payslips_in_the_archived_view(): void
    {
        $employee = Employee::factory()->create();
        $archived = $this->payslipFor($employee, 1000);
        $this->payslipFor(Employee::factory()->create(), 500);
        $archived->archive();

        $this->get(route('payslips.index', ['status' => 'archived']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 1)
                ->where('payslips.data.0.employee_id', $employee->id)
                ->where('payslips.data.0.payslips_count', 1)
            );
    }

    public function test_payslip_index_filters_by_date_generated(): void
    {
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();

        $this->payslipFor($employee, 1000, now());
        $this->payslipFor($employee, 1000, now()->subDays(3));
        $this->payslipFor($otherEmployee, 1000, now()->subDays(3));

        $this->get(route('payslips.index', ['date' => now()->toDateString()]))
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 1)
                ->where('payslips.data.0.employee_id', $employee->id)
                ->where('payslips.data.0.payslips_count', 1)
            );
    }

    public function test_employee_payslips_page_only_lists_that_employees_payslips(): void
    {
        $employee = Employee::factory()->create();
        $this->payslipFor($employee, 1000);
        $this->payslipFor($employee, 2500, now()->subDay());
        $this->payslipFor(Employee::factory()->create(), 9999);

        $this->get(route('payslips.employee-records', $employee->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('payslip/employee-records')
                ->where('employee.id', $employee->id)
                ->has('payslips.data', 2)
                ->where('payslips.data.0.payroll.employee_id', $employee->id)
                ->has('payslips.data.0.payroll.payroll_period')
                ->where('stats.payslips', 2)
                ->where('stats.total_net_pay', fn ($value) => (float) $value === 3500.0)
                ->where('filters.status', 'active')
            );
    }

    public function test_employee_payslips_page_filters_by_status_and_date(): void
    {
        $employee = Employee::factory()->create();
        $today = $this->payslipFor($employee, 1000);
        $this->payslipFor($employee, 2000, now()->subDays(3));
        $archived = $this->payslipFor($employee, 4000, now()->subDays(5));
        $archived->archive();

        $this->get(route('payslips.employee-records', ['employee' => $employee->id, 'date' => now()->toDateString()]))
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 1)
                ->where('payslips.data.0.id', $today->id)
                ->where('stats.payslips', 1)
            );

        $this->get(route('payslips.employee-records', ['employee' => $employee->id, 'status' => 'archived']))
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 1)
                ->where('payslips.data.0.id', $archived->id)
                ->where('stats.total_net_pay', fn ($value) => (float) $value === 4000.0)
            );
    }

    public function test_employee_payslips_page_with_no_payslips_shows_zero_totals(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('payslips.employee-records', $employee->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 0)
                ->where('stats.payslips', 0)
                ->where('stats.total_net_pay', fn ($value) => (float) $value === 0.0)
                ->where('stats.last_generated', null)
            );
    }

    public function test_employee_payslips_page_rejects_bad_filters_and_unknown_employees(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('payslips.employee-records', ['employee' => $employee->id, 'status' => 'hacked']))
            ->assertSessionHasErrors('status');
        $this->get(route('payslips.employee-records', ['employee' => $employee->id, 'date' => 'next monday']))
            ->assertSessionHasErrors('date');
        $this->get(route('payslips.employee-records', 999999))->assertNotFound();
    }

    public function test_employee_payslip_filters_are_sanitized_in_the_service(): void
    {
        $employee = Employee::factory()->create();

        $data = app(PayslipService::class)->employeeData($employee->id, [
            'status' => 'hacked',
            'date' => '2026-13-45',
        ]);

        $this->assertSame(['status' => 'active', 'date' => ''], $data['filters']);
    }

    public function test_payslip_can_be_archived_and_restored_from_the_employee_payslips_page(): void
    {
        $payslip = $this->payslipFor(Employee::factory()->create(), 1000);
        $recordsUrl = route('payslips.employee-records', $payslip->payroll->employee_id);

        $this->from($recordsUrl)
            ->delete(route('payslips.destroy', $payslip))
            ->assertRedirect($recordsUrl);
        $this->assertNotNull($payslip->fresh()->archived_at);

        $this->from($recordsUrl)
            ->patch(route('payslips.restore', $payslip))
            ->assertRedirect($recordsUrl);
        $this->assertNull($payslip->fresh()->archived_at);
    }

    public function test_all_active_payslips_of_an_employee_can_be_archived_from_the_list(): void
    {
        $employee = Employee::factory()->create();
        $first = $this->payslipFor($employee, 1000);
        $second = $this->payslipFor($employee, 2000, now()->subDay());
        $other = $this->payslipFor(Employee::factory()->create(), 500);

        $this->from(route('payslips.index'))
            ->delete(route('payslips.employee-records.archive', $employee->id))
            ->assertRedirect(route('payslips.index'))
            ->assertSessionHas('success', '2 payslips archived successfully.');

        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertNotNull($second->fresh()->archived_at);
        $this->assertNull($other->fresh()->archived_at);
    }

    public function test_archiving_payslips_from_the_list_only_touches_the_filtered_date(): void
    {
        $employee = Employee::factory()->create();
        $today = $this->payslipFor($employee, 1000, now());
        $earlier = $this->payslipFor($employee, 2000, now()->subDays(3));

        $this->delete(route('payslips.employee-records.archive', $employee->id), ['date' => now()->toDateString()])
            ->assertSessionHas('success', '1 payslip archived successfully.');

        $this->assertNotNull($today->fresh()->archived_at);
        $this->assertNull($earlier->fresh()->archived_at);
    }

    public function test_archived_payslips_of_an_employee_can_be_restored_from_the_list(): void
    {
        $employee = Employee::factory()->create();
        $first = $this->payslipFor($employee, 1000);
        $second = $this->payslipFor($employee, 2000, now()->subDay());
        $first->archive();
        $second->archive();

        $this->patch(route('payslips.employee-records.restore', $employee->id))
            ->assertSessionHas('success', '2 payslips restored successfully.');

        $this->assertNull($first->fresh()->archived_at);
        $this->assertNull($second->fresh()->archived_at);
    }

    public function test_archiving_payslips_from_the_list_rejects_bad_dates_and_unknown_employees(): void
    {
        $this->delete(route('payslips.employee-records.archive', Employee::factory()->create()->id), ['date' => 'next monday'])
            ->assertSessionHasErrors('date');

        $this->delete(route('payslips.employee-records.archive', 999999))->assertNotFound();
    }
}