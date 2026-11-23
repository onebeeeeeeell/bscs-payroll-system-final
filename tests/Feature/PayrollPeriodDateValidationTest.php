<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodDateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_create_rejects_past_start_end_and_pay_dates(): void
    {
        foreach (['start_date', 'end_date', 'pay_date'] as $field) {
            $dates = $this->validDates();
            $dates[$field] = today()->subDay()->toDateString();

            $this->from(route('payroll_periods.create'))
                ->post(route('payroll_periods.store'), $dates)
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('payroll_periods', 0);
    }

    public function test_update_rejects_past_start_end_and_pay_dates(): void
    {
        $period = PayrollPeriod::factory()->create();

        foreach (['start_date', 'end_date', 'pay_date'] as $field) {
            $dates = $this->validDates();
            $dates[$field] = today()->subDay()->toDateString();

            $this->from(route('payroll_periods.edit', $period))
                ->patch(route('payroll_periods.update', $period), $dates)
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(today()->toDateString(), $period->fresh()->start_date);
    }

    public function test_today_is_allowed_for_start_end_and_pay_dates(): void
    {
        $dates = [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'pay_date' => today()->toDateString(),
            'status' => 'Pending',
        ];

        $this->post(route('payroll_periods.store'), $dates)
            ->assertRedirect(route('payroll_periods.index'));

        $this->assertDatabaseHas('payroll_periods', $dates);
    }

    public function test_pay_date_cannot_be_before_the_end_date_on_create_or_update(): void
    {
        $dates = $this->validDates();
        $dates['pay_date'] = today()->addDays(12)->toDateString();

        $this->from(route('payroll_periods.create'))
            ->post(route('payroll_periods.store'), $dates)
            ->assertSessionHasErrors('pay_date');

        $period = PayrollPeriod::factory()->create();

        $this->from(route('payroll_periods.edit', $period))
            ->patch(route('payroll_periods.update', $period), $dates)
            ->assertSessionHasErrors('pay_date');
    }

    /**
     * @return array{start_date: string, end_date: string, pay_date: string, status: string}
     */
    private function validDates(): array
    {
        $today = today();

        return [
            'start_date' => $today->toDateString(),
            'end_date' => $today->copy()->addDays(13)->toDateString(),
            'pay_date' => $today->copy()->addDays(14)->toDateString(),
            'status' => 'Pending',
        ];
    }
}
