<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }

    public function test_dashboard_totals_only_count_active_payrolls(): void
    {
        Payroll::factory()->create(['gross_pay' => 1000, 'total_deductions' => 100, 'net_pay' => 900]);
        Payroll::factory()->create(['gross_pay' => 2000, 'total_deductions' => 200, 'net_pay' => 1800]);
        // archived payrolls are not counted
        Payroll::factory()->create(['gross_pay' => 5000, 'total_deductions' => 500, 'net_pay' => 4500, 'archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.payrolls', 2)
                ->where('stats.gross_pay', 3000)
                ->where('stats.total_deductions', 300)
                ->where('stats.net_pay', 2700)
            );
    }

    public function test_dashboard_shows_zero_totals_when_there_are_no_payrolls(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.payrolls', 0)
                ->where('stats.net_pay', 0)
                ->has('recent_payrolls', 0)
            );
    }

    public function test_dashboard_counts_active_payrolls_without_a_payslip(): void
    {
        $withPayslip = Payroll::factory()->create();
        Payslip::factory()->create(['payroll_id' => $withPayslip->id]);
        Payroll::factory()->count(2)->create();
        // archived payroll without a payslip is not counted
        Payroll::factory()->create(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('stats.awaiting_payslip', 2));
    }

    public function test_dashboard_recent_payrolls_are_the_latest_five_with_employee_and_period(): void
    {
        foreach (range(6, 1) as $daysAgo) {
            Payroll::factory()->create(['date_processed' => today()->subDays($daysAgo)->toDateString()]);
        }

        $newest = Payroll::query()->orderByDesc('date_processed')->first();

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('recent_payrolls', 5)
                ->where('recent_payrolls.0.id', $newest->id)
                ->has('recent_payrolls.0.employee.first_name')
                ->has('recent_payrolls.0.payroll_period.start_date')
            );
    }

    public function test_dashboard_lists_every_period_status_and_skips_archived_periods(): void
    {
        PayrollPeriod::factory()->count(2)->create(['status' => 'Pending']);
        PayrollPeriod::factory()->create(['status' => 'Paid']);
        PayrollPeriod::factory()->create(['status' => 'Paid', 'archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.pending_periods', 2)
                ->has('period_statuses', 4)
                ->where('period_statuses.0', ['status' => 'Pending', 'total' => 2])
                ->where('period_statuses.1', ['status' => 'Processing', 'total' => 0])
                ->where('period_statuses.2', ['status' => 'Paid', 'total' => 1])
                ->where('period_statuses.3', ['status' => 'Failed', 'total' => 0])
            );
    }

    public function test_dashboard_departments_only_count_active_employees(): void
    {
        $accounting = Department::factory()->create(['department_name' => 'Accounting']);
        $billing = Department::factory()->create(['department_name' => 'Billing']);
        $cashier = Department::factory()->create(['department_name' => 'Cashier']);

        Employee::factory()->count(2)->create(['department_id' => $accounting->id]);
        // archived employees are left out
        Employee::factory()->create(['department_id' => $accounting->id, 'archived_at' => now()]);
        Employee::factory()->create(['department_id' => $billing->id]);
        // a department with only archived employees still shows up, with 0
        Employee::factory()->create(['department_id' => $cashier->id, 'archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('departments', 3)
                ->where('departments.0.department_name', 'Accounting')
                ->where('departments.0.employees_count', 2)
                ->where('departments.1.department_name', 'Billing')
                ->where('departments.1.employees_count', 1)
                ->where('departments.2.department_name', 'Cashier')
                ->where('departments.2.employees_count', 0)
            );
    }

    public function test_dashboard_active_employees_total_is_the_sum_across_departments(): void
    {
        $first = Department::factory()->create();
        $second = Department::factory()->create();

        Employee::factory()->count(3)->create(['department_id' => $first->id]);
        Employee::factory()->count(2)->create(['department_id' => $second->id]);
        // archived employees are not part of the total
        Employee::factory()->count(2)->create(['department_id' => $second->id, 'archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('stats.active_employees', 5));
    }
}