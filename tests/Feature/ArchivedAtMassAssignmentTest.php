<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedAtMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_at_is_not_mass_assignable(): void
    {
        foreach ([new Payroll, new PayrollPeriod, new Payslip] as $model) {
            $model->fill(['archived_at' => now()]);

            $this->assertNull($model->archived_at, $model::class.' accepted archived_at through fill().');
        }
    }

    public function test_archive_and_restore_still_work(): void
    {
        foreach ([Payroll::factory()->create(), PayrollPeriod::factory()->create(), Payslip::factory()->create()] as $model) {
            $model->archive();
            $this->assertNotNull($model->fresh()->archived_at);

            $model->restore();
            $this->assertNull($model->fresh()->archived_at);
        }
    }

    public function test_payroll_update_request_cannot_archive_through_extra_input(): void
    {
        $payroll = Payroll::factory()->create([
            'gross_pay' => 1000,
            'total_deductions' => 100,
            'net_pay' => 900,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('payrolls.update', $payroll), [
                'gross_pay' => 5000,
                'total_deductions' => 100,
                'date_processed' => today()->toDateString(),
                'archived_at' => now()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($payroll->fresh()->archived_at);
        $this->assertEquals(5000, (float) $payroll->fresh()->gross_pay);
    }
}