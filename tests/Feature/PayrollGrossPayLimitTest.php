<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollProcessingService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollGrossPayLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_manual_payroll_update_rejects_gross_pay_above_30000_and_accepts_the_limit(): void
    {
        $payroll = Payroll::factory()->create();
        $payload = [
            'total_deductions' => '0',
            'date_processed' => today()->toDateString(),
        ];

        $this->patch(route('payrolls.update', $payroll), $payload + ['gross_pay' => '30000.01'])
            ->assertSessionHasErrors('gross_pay');

        $this->patch(route('payrolls.update', $payroll), $payload + ['gross_pay' => '30000.00'])
            ->assertRedirect(route('payrolls.index'));

        $this->assertEquals(30000, (float) $payroll->fresh()->gross_pay);
    }

    public function test_payroll_service_rejects_direct_gross_pay_above_30000(): void
    {
        $this->expectException(ValidationException::class);

        app(PayrollService::class)->update(Payroll::factory()->create(), [
            'gross_pay' => '30000.01',
            'total_deductions' => '0',
            'date_processed' => today()->toDateString(),
        ]);
    }

    public function test_generated_gross_pay_above_30000_blocks_processing(): void
    {
        $period = PayrollPeriod::factory()->create();
        $employee = Employee::factory()->create(['daily_rate' => 40000]);
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'work_date' => $period->start_date,
            'hours_worked' => 8,
            'overtime_hours' => 0,
            'time_out' => '17:00',
        ]);

        $service = app(PayrollProcessingService::class);
        $preview = $service->pageData(['payroll_period_id' => $period->id])['preview'];
        $row = collect($preview['rows'])->firstWhere('employee_id', $employee->id);

        $this->assertSame(PayrollProcessingService::STATUS_GROSS_PAY_EXCEEDS_LIMIT, $row['status']);
        $this->assertSame(1, $preview['summary']['blocked']);

        $this->post(route('payrolls.process.store'), ['payroll_period_id' => $period->id])
            ->assertSessionHasErrors('payroll_period_id');

        $this->assertDatabaseCount('payrolls', 0);
    }
}
