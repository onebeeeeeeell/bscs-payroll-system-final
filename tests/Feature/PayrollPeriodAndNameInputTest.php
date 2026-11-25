<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodAndNameInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_new_period_cannot_start_as_paid_and_defaults_to_pending(): void
    {
        $this->post(route('payroll_periods.store'), $this->dates(0, ['status' => 'Paid']))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('payroll_periods', 0);

        $data = $this->dates(0);
        unset($data['status']);

        $this->post(route('payroll_periods.store'), $data)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payroll_periods', ['status' => 'Pending']);
    }

    public function test_paid_period_cannot_be_flipped_back(): void
    {
        $paidDates = [
            'start_date' => today()->subDays(20)->toDateString(),
            'end_date' => today()->subDays(7)->toDateString(),
            'pay_date' => today()->subDays(6)->toDateString(),
        ];
        $period = PayrollPeriod::factory()->create($paidDates + ['status' => 'Paid']);

        $this->patch(route('payroll_periods.update', $period), $paidDates + ['status' => 'Pending'])
            ->assertSessionHasErrors('status');

        $this->assertSame('Paid', $period->fresh()->status);

        $this->patch(route('payroll_periods.update', $period), $paidDates + ['status' => 'Paid'])
            ->assertSessionHasNoErrors();
    }

    public function test_period_cannot_be_marked_paid_before_its_pay_date(): void
    {
        $period = PayrollPeriod::factory()->create();

        $this->patch(route('payroll_periods.update', $period), $this->dates(0, ['status' => 'Paid']))
            ->assertSessionHasErrors('status');

        $this->assertSame('Pending', $period->fresh()->status);
    }

    public function test_paid_period_with_a_future_pay_date_is_not_locked_and_can_be_fixed(): void
    {
        // wrong data: Paid, but the pay date is still in the future
        $period = PayrollPeriod::factory()->create(['status' => 'Paid']);

        $this->patch(route('payroll_periods.update', $period), $this->dates(0, ['status' => 'Pending']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Pending', $period->fresh()->status);
    }

    public function test_new_period_dates_must_be_within_the_current_year(): void
    {
        foreach (['start_date', 'end_date', 'pay_date'] as $field) {
            $this->post(route('payroll_periods.store'), $this->dates(0, [$field => now()->addYear()->startOfYear()->addDays(10)->toDateString()]))
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('payroll_periods', 0);
    }

    public function test_period_dates_must_be_exact_y_m_d(): void
    {
        foreach (['start_date', 'end_date', 'pay_date'] as $field) {
            $this->post(route('payroll_periods.store'), $this->dates(0, [$field => 'next monday']))
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('payroll_periods', 0);
    }

    public function test_overlapping_active_periods_are_rejected_but_adjacent_and_archived_ones_are_fine(): void
    {
        // today .. today+13
        $existing = PayrollPeriod::factory()->create();

        // shares the last day -> overlap
        $this->post(route('payroll_periods.store'), $this->dates(13))
            ->assertSessionHasErrors('start_date');

        // starts the day after -> fine
        $this->post(route('payroll_periods.store'), $this->dates(14))
            ->assertSessionHasNoErrors();

        // an archived period no longer blocks its dates
        $existing->archive();

        $this->post(route('payroll_periods.store'), $this->dates(0))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payroll_periods', 3);
    }

    public function test_a_period_can_be_edited_without_overlapping_itself(): void
    {
        $period = PayrollPeriod::factory()->create();

        $this->patch(route('payroll_periods.update', $period), $this->dates(0, ['status' => 'Processing']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Processing', $period->fresh()->status);
    }

    public function test_started_period_can_still_be_edited_but_not_moved_to_a_past_date(): void
    {
        $period = PayrollPeriod::factory()->create([
            'start_date' => today()->subDays(3)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
            'pay_date' => today()->addDays(11)->toDateString(),
        ]);

        // dates untouched, only the status changes
        $this->patch(route('payroll_periods.update', $period), [
            'start_date' => today()->subDays(3)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
            'pay_date' => today()->addDays(11)->toDateString(),
            'status' => 'Processing',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Processing', $period->fresh()->status);

        // a different past start date is still rejected
        $this->patch(route('payroll_periods.update', $period), [
            'start_date' => today()->subDays(2)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
            'pay_date' => today()->addDays(11)->toDateString(),
            'status' => 'Processing',
        ])->assertSessionHasErrors('start_date');
    }

    public function test_employee_names_must_contain_a_letter(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        foreach (['first_name', 'last_name', 'suffix'] as $field) {
            foreach (['---', '...', "'", '.-.'] as $value) {
                $this->post(route('employees.store'), $this->employeeData($position, $department, [$field => $value]))
                    ->assertSessionHasErrors($field);
            }
        }

        $employee = Employee::factory()->create();

        $this->patch(route('employees.update', $employee), ['first_name' => '---'])
            ->assertSessionHasErrors('first_name');

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_names_with_hyphens_apostrophes_and_periods_still_work(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $this->post(route('employees.store'), $this->employeeData($position, $department, [
            'first_name' => 'Anne-Marie',
            'last_name' => "O'Brien",
            'suffix' => 'Jr',
        ]))->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['first_name' => 'Anne-Marie', 'last_name' => "O'Brien", 'suffix' => 'Jr']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function dates(int $offsetDays, array $overrides = []): array
    {
        $start = today()->addDays($offsetDays);

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(13)->toDateString(),
            'pay_date' => $start->copy()->addDays(14)->toDateString(),
            'status' => 'Pending',
            ...$overrides,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function employeeData(Position $position, Department $department, array $overrides = []): array
    {
        return [
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
            'pin' => '123456',
            ...$overrides,
        ];
    }
}