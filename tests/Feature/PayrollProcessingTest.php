<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PayrollProcessingTest extends TestCase
{
    use RefreshDatabase;

    private function period(string $status = 'Pending'): PayrollPeriod
    {
        return PayrollPeriod::factory()->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-15',
            'pay_date' => '2026-10-16',
            'status' => $status,
        ]);
    }

    private function attendance(Employee $employee, string $date, float $hours, float $overtime = 0, ?string $timeOut = '17:00'): Attendance
    {
        return Attendance::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => $date,
            'time_out' => $timeOut,
            'hours_worked' => $hours,
            'overtime_hours' => $overtime,
        ]);
    }

    /** Dalawang type: A (may lumang 50, bagong 100) at B (25). Latest lang ang bibilangin = 125. */
    private function rates(): void
    {
        $typeA = ContributionType::factory()->create();
        $typeB = ContributionType::factory()->create();

        ContributionRate::factory()->create(['contribution_type_id' => $typeA->id, 'rate' => 50]);
        ContributionRate::factory()->create(['contribution_type_id' => $typeA->id, 'rate' => 100]);
        ContributionRate::factory()->create(['contribution_type_id' => $typeB->id, 'rate' => 25]);
    }

    /** 800/day: 8 hrs (+2 OT), 8 hrs, 4 hrs = 2.5 paid days; gross 2,250. */
    private function workedEmployee(array $attributes = []): Employee
    {
        $employee = Employee::factory()->create(['daily_rate' => 800] + $attributes);

        $this->attendance($employee, '2026-10-01', 10, 2);
        $this->attendance($employee, '2026-10-02', 8);
        $this->attendance($employee, '2026-10-03', 4);

        return $employee;
    }

    private function previewRow(PayrollProcessingService $service, PayrollPeriod $period, Employee $employee): ?array
    {
        $preview = $service->pageData(['payroll_period_id' => $period->id])['preview'];

        return collect($preview['rows'])->firstWhere('employee_id', $employee->id);
    }

    public function test_preview_computes_paid_days_gross_deductions_and_net(): void
    {
        $period = $this->period();
        $this->rates();
        $employee = $this->workedEmployee();

        $data = app(PayrollProcessingService::class)->pageData(['payroll_period_id' => $period->id]);
        $row = collect($data['preview']['rows'])->firstWhere('employee_id', $employee->id);

        $this->assertEquals(2.5, $row['paid_days']);
        $this->assertEquals(2, $row['overtime_hours']);
        $this->assertEquals(2250, $row['gross_pay']);
        $this->assertEquals(125, $row['total_deductions']);
        $this->assertEquals(2125, $row['net_pay']);
        $this->assertSame('ready', $row['status']);
        $this->assertEquals(2125, $data['preview']['summary']['net_pay']);
        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_attendance_outside_the_period_and_deleted_attendance_are_not_counted(): void
    {
        $period = $this->period();
        $employee = $this->workedEmployee();
        $this->attendance($employee, '2026-09-30', 8);
        $this->attendance($employee, '2026-10-16', 8);
        $this->attendance($employee, '2026-10-04', 8)->delete();

        $row = $this->previewRow(app(PayrollProcessingService::class), $period, $employee);

        $this->assertEquals(2.5, $row['paid_days']);
    }

    public function test_employee_without_attendance_is_skipped_and_does_not_block_saving(): void
    {
        $period = $this->period();
        $this->rates();
        $this->workedEmployee();
        $idle = Employee::factory()->create(['daily_rate' => 800]);

        $data = app(PayrollProcessingService::class)->pageData(['payroll_period_id' => $period->id]);
        $row = collect($data['preview']['rows'])->firstWhere('employee_id', $idle->id);

        $this->assertSame('no_attendance', $row['status']);
        $this->assertSame(1, $data['preview']['summary']['no_attendance']);
        $this->assertSame(0, $data['preview']['summary']['blocked']);
    }

    public function test_archived_employees_are_not_included(): void
    {
        $period = $this->period();
        $archived = $this->workedEmployee(['archived_at' => now()]);

        $row = $this->previewRow(app(PayrollProcessingService::class), $period, $archived);

        $this->assertNull($row);
    }

    public function test_open_time_out_blocks_saving(): void
    {
        $period = $this->period();
        $this->rates();
        $employee = Employee::factory()->create(['daily_rate' => 800]);
        $this->attendance($employee, '2026-10-01', 0, 0, null);

        $this->assertSame('open_time_out', $this->previewRow(app(PayrollProcessingService::class), $period, $employee)['status']);

        $this->actingAs(User::factory()->create())
            ->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id])
            ->assertSessionHasErrors('payroll_period_id');

        $this->assertDatabaseCount('payrolls', 0);
        $this->assertSame('Pending', $period->fresh()->status);
    }

    public function test_deductions_exceeding_gross_block_saving(): void
    {
        $period = $this->period();
        $this->rates();
        $employee = Employee::factory()->create(['daily_rate' => 100]);
        $this->attendance($employee, '2026-10-01', 8);

        $this->assertSame('deductions_exceed_gross', $this->previewRow(app(PayrollProcessingService::class), $period, $employee)['status']);

        $this->actingAs(User::factory()->create())
            ->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id])
            ->assertSessionHasErrors('payroll_period_id');

        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_store_creates_payrolls_and_deductions_and_marks_period_processing(): void
    {
        $period = $this->period();
        $this->rates();
        $employee = $this->workedEmployee();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id])
            ->assertRedirect(route('payrolls.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'user_id' => $user->id,
            'gross_pay' => '2250.00',
            'total_deductions' => '125.00',
            'net_pay' => '2125.00',
        ]);
        $this->assertDatabaseCount('deductions', 2);
        $this->assertEqualsCanonicalizing([100, 25], \App\Models\Deduction::query()->pluck('amount')->map(fn ($amount) => (int) $amount)->all());
        $this->assertSame('Processing', $period->fresh()->status);
    }

    public function test_a_processed_period_cannot_be_processed_again(): void
    {
        $period = $this->period();
        $this->rates();
        $this->workedEmployee();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id]);
        $this->actingAs($user)
            ->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id])
            ->assertSessionHasErrors('payroll_period_id');

        $this->assertDatabaseCount('payrolls', 1);
    }

    public function test_preview_rejects_a_period_that_is_not_processable(): void
    {
        $paid = $this->period('Paid');

        $this->expectException(ValidationException::class);

        app(PayrollProcessingService::class)->pageData(['payroll_period_id' => $paid->id]);
    }

    public function test_search_is_sanitized_and_only_filters_the_table(): void
    {
        $period = $this->period();
        $this->workedEmployee(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->workedEmployee(['first_name' => 'Daniel', 'last_name' => 'Reyes']);

        $data = app(PayrollProcessingService::class)->pageData([
            'payroll_period_id' => $period->id,
            'search' => ' <b>Maria</b>   Santos ',
        ]);

        $this->assertSame('Maria Santos', $data['filters']['search']);
        $this->assertCount(1, $data['preview']['rows']);
        $this->assertSame(2, $data['preview']['summary']['employees']);
    }

    public function test_process_page_renders_for_authenticated_users_only(): void
    {
        // Hindi kailangan ng built assets (npm run build) para sa test na ito
        $this->withoutVite();

        $period = $this->period();

        $this->get(route('payrolls.process.create'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('payrolls.process.create', ['payroll_period_id' => $period->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('payroll/create')
                ->has('periods', 1)
                ->has('preview.rows'));
    }

    public function test_period_id_must_be_an_integer(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('payrolls.process.create', ['payroll_period_id' => 'abc']))
            ->assertSessionHasErrors('payroll_period_id');

        $this->actingAs(User::factory()->create())
            ->post(route('payrolls.process.store'), [])
            ->assertSessionHasErrors('payroll_period_id');
    }
}