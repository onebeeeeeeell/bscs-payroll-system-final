<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_archived_payrolls_are_hidden_until_the_archived_filter_is_used(): void
    {
        $payroll = Payroll::factory()->create();
        $this->delete(route('payrolls.destroy', $payroll));

        $this->get(route('payrolls.index'))
            ->assertInertia(fn ($page) => $page->has('payrolls.data', 0));

        $this->get(route('payrolls.index', ['status' => 'archived']))
            ->assertInertia(fn ($page) => $page->has('payrolls.data', 1)->where('payrolls.data.0.id', $payroll->id));

        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id]);
    }

    public function test_payroll_records_filter_by_date_department_and_position(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id, 'position_id' => $position->id]);

        $match = Payroll::factory()->create(['employee_id' => $employee->id, 'date_processed' => now()]);
        Payroll::factory()->create(['date_processed' => now()]);
        Payroll::factory()->create(['employee_id' => $employee->id, 'date_processed' => now()->subYear()]);

        $this->get(route('payrolls.index', [
            'date' => today()->toDateString(),
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]))->assertInertia(fn ($page) => $page->has('payrolls.data', 1)->where('payrolls.data.0.id', $match->id));
    }

    public function test_payroll_periods_can_be_archived_even_with_payrolls_and_restored(): void
    {
        $payroll = Payroll::factory()->create();
        $period = $payroll->payrollPeriod;

        $this->delete(route('payroll_periods.destroy', $period))->assertRedirect();
        $this->assertNotNull($period->fresh()->archived_at);
        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id]);

        $this->get(route('payroll_periods.index'))
            ->assertInertia(fn ($page) => $page->has('payrollPeriods.data', 0));

        $this->patch(route('payroll_periods.restore', $period))->assertRedirect();
        $this->assertNull($period->fresh()->archived_at);
    }

    public function test_payroll_date_filter_finds_past_records_and_covers_the_current_year_unless_records_exist_earlier(): void
    {
        $year = now()->year;

        $this->get(route('payrolls.index'))
            ->assertInertia(fn ($page) => $page
                ->where('dateBounds.min', "$year-01-01")
                ->where('dateBounds.max', "$year-12-31")
            );

        $employee = Employee::factory()->create();
        $past = Payroll::factory()->create(['employee_id' => $employee->id, 'date_processed' => now()->subYears(2)]);
        Payroll::factory()->create(['date_processed' => now()]);

        // a past date is accepted and finds the past record
        $this->get(route('payrolls.index', ['date' => now()->subYears(2)->toDateString()]))
            ->assertSessionHasNoErrors()
            ->assertInertia(fn ($page) => $page
                ->has('payrolls.data', 1)
                ->where('payrolls.data.0.id', $past->id)
                ->where('dateBounds.min', ($year - 2).'-01-01')
            );
    }

    public function test_payslip_date_filter_finds_past_payslips_and_covers_the_current_year_unless_payslips_exist_earlier(): void
    {
        $year = now()->year;

        $this->get(route('payslips.index'))
            ->assertInertia(fn ($page) => $page
                ->where('dateBounds.min', "$year-01-01")
                ->where('dateBounds.max', "$year-12-31")
            );

        $past = Payslip::factory()->create(['date_generated' => now()->subYears(2)]);
        Payslip::factory()->create(['date_generated' => now()]);

        $this->get(route('payslips.index', ['date' => now()->subYears(2)->toDateString()]))
            ->assertSessionHasNoErrors()
            ->assertInertia(fn ($page) => $page
                ->has('payslips.data', 1)
                ->where('payslips.data.0.employee_id', $past->payroll->employee_id)
                ->where('dateBounds.min', ($year - 2).'-01-01')
            );
    }

    public function test_payroll_periods_filter_by_date(): void
    {
        $match = PayrollPeriod::factory()->create([
            'start_date' => today()->subDays(5)->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
        ]);
        PayrollPeriod::factory()->create([
            'start_date' => today()->subYear()->toDateString(),
            'end_date' => today()->subYear()->addDays(14)->toDateString(),
        ]);

        $this->get(route('payroll_periods.index', ['date' => today()->toDateString()]))
            ->assertInertia(fn ($page) => $page->has('payrollPeriods.data', 1)->where('payrollPeriods.data.0.id', $match->id));
    }

    public function test_payroll_period_date_filter_only_covers_the_current_year_unless_periods_exist_in_other_years(): void
    {
        $year = now()->year;

        $this->get(route('payroll_periods.index'))
            ->assertInertia(fn ($page) => $page
                ->where('dateBounds.min', "$year-01-01")
                ->where('dateBounds.max', "$year-12-31")
            );

        PayrollPeriod::factory()->create([
            'start_date' => today()->subYears(2)->toDateString(),
            'end_date' => today()->subYears(2)->addDays(13)->toDateString(),
            'pay_date' => today()->subYears(2)->addDays(14)->toDateString(),
        ]);

        $this->get(route('payroll_periods.index'))
            ->assertInertia(fn ($page) => $page->where('dateBounds.min', ($year - 2).'-01-01'));
    }

    public function test_archived_periods_cannot_be_processed(): void
    {
        $period = PayrollPeriod::factory()->create(['status' => 'Pending', 'archived_at' => now()]);

        $this->get(route('payrolls.process.create'))
            ->assertInertia(fn ($page) => $page->where('periods', fn ($periods) => collect($periods)->doesntContain('id', $period->id)));
    }

    public function test_payslips_are_archived_instead_of_deleted_and_can_be_restored(): void
    {
        $payslip = Payslip::factory()->create();

        $this->delete(route('payslips.destroy', $payslip))->assertRedirect();
        $this->assertNotNull($payslip->fresh()->archived_at);

        $this->get(route('payslips.index'))
            ->assertInertia(fn ($page) => $page->has('payslips.data', 0));
        $this->get(route('payslips.index', ['status' => 'archived']))
            ->assertInertia(fn ($page) => $page->has('payslips.data', 1));

        $this->patch(route('payslips.restore', $payslip))->assertRedirect();
        $this->assertNull($payslip->fresh()->archived_at);
    }

    public function test_organization_settings_page_is_gone(): void
    {
        $this->get('/settings/organization')->assertNotFound();
    }
}