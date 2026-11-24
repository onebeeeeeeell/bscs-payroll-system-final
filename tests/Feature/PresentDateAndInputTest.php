<?php

namespace Tests\Feature;

use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use App\Services\ContributionRateService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PresentDateAndAmountInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Hindi kailangan ng built assets (npm run build) para sa mga page test dito
        $this->withoutVite();

        $this->actingAs(User::factory()->create());
    }

    public function test_date_filters_only_accept_today(): void
    {
        foreach (['payrolls.index', 'payslips.index', 'payroll_periods.index'] as $routeName) {
            $this->get(route($routeName, ['date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('date');
            $this->get(route($routeName, ['date' => today()->addDay()->toDateString()]))->assertSessionHasErrors('date');
            $this->get(route($routeName, ['date' => today()->toDateString()]))->assertOk();
        }
    }

    public function test_payroll_amounts_accept_only_numbers_with_up_to_two_decimals(): void
    {
        $payroll = Payroll::factory()->create();
        $today = today()->toDateString();

        foreach (['12abc', '1,000', '-5', '1e3', '12.345', ''] as $bad) {
            $this->patch(route('payrolls.update', $payroll), ['gross_pay' => $bad, 'total_deductions' => '0', 'date_processed' => $today])
                ->assertSessionHasErrors('gross_pay');
        }

        $this->patch(route('payrolls.update', $payroll), ['gross_pay' => '5000.50', 'total_deductions' => '100', 'date_processed' => $today])
            ->assertRedirect(route('payrolls.index'));

        $this->assertEquals(4900.5, (float) $payroll->fresh()->net_pay);
    }

    public function test_payroll_date_processed_must_be_today(): void
    {
        $payroll = Payroll::factory()->create();
        $payload = ['gross_pay' => '5000', 'total_deductions' => '100'];

        $this->patch(route('payrolls.update', $payroll), $payload + ['date_processed' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors('date_processed');
        $this->patch(route('payrolls.update', $payroll), $payload + ['date_processed' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors('date_processed');

        $this->patch(route('payrolls.update', $payroll), $payload + ['date_processed' => today()->toDateString()])
            ->assertRedirect(route('payrolls.index'));
        $this->assertTrue(Carbon::parse($payroll->fresh()->date_processed)->isToday());
    }

    public function test_contribution_rate_accepts_only_numbers_with_up_to_two_decimals(): void
    {
        $type = ContributionType::factory()->create();

        foreach (['abc', '12a', '1,000', '12.345', '-5', ''] as $bad) {
            $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'fixed', 'rate' => $bad])
                ->assertSessionHasErrors('rate');
        }

        $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'percent', 'rate' => '100.01'])
            ->assertSessionHasErrors('rate');

        $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'fixed', 'rate' => '150.25'])
            ->assertRedirect(route('contribution_rates.index'));

        $this->assertDatabaseCount('contribution_rates', 1);
    }

    public function test_payroll_setup_search_is_limited_to_30_letters(): void
    {
        foreach (['contribution_types.index', 'contribution_rates.index', 'departments.index', 'positions.index', 'deductions.index'] as $routeName) {
            $this->get(route($routeName, ['search' => str_repeat('a', 31)]))->assertSessionHasErrors('search');
            $this->get(route($routeName, ['search' => 'abc123']))->assertSessionHasErrors('search');
            $this->get(route($routeName, ['search' => 'abc!']))->assertSessionHasErrors('search');
            $this->get(route($routeName, ['search' => str_repeat('a', 30)]))->assertOk();
        }
    }

    public function test_services_normalize_valid_amounts_when_called_directly(): void
    {
        $type = ContributionType::factory()->create();

        foreach ([[' 1500.5 ', 1500.5], ['15', 15.0], ['0007.25', 7.25], [1500, 1500.0], [12.5, 12.5]] as [$input, $expected]) {
            $rate = app(ContributionRateService::class)->create([
                'contribution_type_id' => $type->id,
                'calculation_type' => ContributionRate::TYPE_FIXED,
                'rate' => $input,
            ]);

            $this->assertEquals($expected, (float) $rate->fresh()->rate);
        }
    }

    public function test_services_reject_garbage_amounts_instead_of_repairing_them(): void
    {
        $type = ContributionType::factory()->create();

        foreach ([' 1,500.5abc ', 'abc12', '-100', '12.345', '1e3', '', null, ['1']] as $bad) {
            try {
                app(ContributionRateService::class)->create([
                    'contribution_type_id' => $type->id,
                    'calculation_type' => ContributionRate::TYPE_FIXED,
                    'rate' => $bad,
                ]);
                $this->fail('Rate accepted '.json_encode($bad));
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('rate', $exception->errors());
            }

            try {
                app(PayrollService::class)->update(Payroll::factory()->create(), [
                    'gross_pay' => $bad,
                    'total_deductions' => '0',
                    'date_processed' => today()->toDateString(),
                ]);
                $this->fail('Gross pay accepted '.json_encode($bad));
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('gross_pay', $exception->errors());
            }
        }

        $this->assertDatabaseCount('contribution_rates', 0);
    }

    public function test_payslip_edit_and_notifications_are_gone(): void
    {
        $payslip = Payslip::factory()->create();

        $this->get('/payslips/'.$payslip->id.'/edit')->assertNotFound();
        $this->patch('/payslips/'.$payslip->id, ['date_generated' => now()->toDateTimeString()])->assertStatus(405);
        $this->get('/notifications')->assertNotFound();
    }
}