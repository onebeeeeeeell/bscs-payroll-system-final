<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollLockTest extends TestCase
{
    use RefreshDatabase;

    private function payrollIn(string $status): Payroll
    {
        return Payroll::factory()->create([
            'payroll_period_id' => PayrollPeriod::factory()->create(['status' => $status])->id,
            'gross_pay' => 1000,
            'total_deductions' => 100,
            'net_pay' => 900,
        ]);
    }

    private function payload(): array
    {
        return ['gross_pay' => 5000, 'total_deductions' => 100, 'date_processed' => now()->toDateTimeString()];
    }

    public function test_payroll_in_a_paid_period_cannot_be_updated(): void
    {
        $payroll = $this->payrollIn('Paid');

        $this->actingAs(User::factory()->create())
            ->patch(route('payrolls.update', $payroll), $this->payload())
            ->assertSessionHasErrors('gross_pay');

        $this->assertEquals(1000, $payroll->fresh()->gross_pay);
    }

    public function test_payroll_in_a_paid_period_cannot_be_deleted(): void
    {
        $payroll = $this->payrollIn('Paid');

        $this->actingAs(User::factory()->create())
            ->delete(route('payrolls.destroy', $payroll))
            ->assertSessionHasErrors('payroll');

        $this->assertDatabaseHas('payrolls', ['id' => $payroll->id]);
    }

    public function test_edit_page_redirects_to_show_for_a_paid_period(): void
    {
        $payroll = $this->payrollIn('Paid');

        $this->actingAs(User::factory()->create())
            ->get(route('payrolls.edit', $payroll))
            ->assertRedirect(route('payrolls.show', $payroll));
    }

    public function test_payroll_in_a_processing_period_can_still_be_updated_with_server_computed_net(): void
    {
        $payroll = $this->payrollIn('Processing');

        $this->actingAs(User::factory()->create())
            ->patch(route('payrolls.update', $payroll), $this->payload() + ['net_pay' => 999999, 'user_id' => 999])
            ->assertRedirect(route('payrolls.index'));

        $fresh = $payroll->fresh();
        $this->assertEquals(5000, $fresh->gross_pay);
        $this->assertEquals(4900, $fresh->net_pay);
        $this->assertEquals($payroll->user_id, $fresh->user_id);
    }

    public function test_deductions_cannot_exceed_gross(): void
    {
        $payroll = $this->payrollIn('Processing');

        $this->actingAs(User::factory()->create())
            ->patch(route('payrolls.update', $payroll), ['gross_pay' => 100, 'total_deductions' => 500, 'date_processed' => now()->toDateTimeString()])
            ->assertSessionHasErrors('total_deductions');
    }
}