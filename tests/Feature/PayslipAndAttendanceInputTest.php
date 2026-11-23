<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipAndAttendanceInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_payslip_rejects_archived_payroll(): void
    {
        $payroll = Payroll::factory()->create(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->post(route('payslips.store'), [
                'payroll_id' => $payroll->id,
                'date_generated' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('payroll_id');

        $this->assertDatabaseCount('payslips', 0);
    }

    public function test_payslip_rejects_loose_and_future_dates(): void
    {
        $payroll = Payroll::factory()->create();
        $user = User::factory()->create();

        foreach (['next monday', now()->addDays(5)->format('Y-m-d\TH:i')] as $value) {
            $this->actingAs($user)
                ->post(route('payslips.store'), ['payroll_id' => $payroll->id, 'date_generated' => $value])
                ->assertSessionHasErrors('date_generated');
        }

        $this->assertDatabaseCount('payslips', 0);
    }

    public function test_payslip_is_created_for_active_payroll_with_valid_date(): void
    {
        $payroll = Payroll::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('payslips.store'), [
                'payroll_id' => $payroll->id,
                'date_generated' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Payslip::query()->where('payroll_id', $payroll->id)->count());
    }

    public function test_attendance_rejects_a_second_record_for_the_same_employee_and_day(): void
    {
        $employee = Employee::factory()->create();
        Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->toDateString()]);

        $this->actingAs(User::factory()->create())
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
            ])
            ->assertSessionHasErrors('work_date');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_attendance_update_may_keep_its_own_date_but_not_take_another_record_date(): void
    {
        $employee = Employee::factory()->create();
        $first = Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->toDateString()]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->subDay()->toDateString()]);
        $user = User::factory()->create();

        $payload = ['employee_id' => $employee->id, 'time_in' => '08:00', 'time_out' => '16:00'];

        $this->actingAs($user)
            ->put(route('attendance.update', $first), $payload + ['work_date' => today()->toDateString()])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('attendance.update', $first), $payload + ['work_date' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors('work_date');
    }

    public function test_deleted_record_does_not_block_a_new_one_but_blocks_its_own_restore(): void
    {
        $employee = Employee::factory()->create();
        $old = Attendance::factory()->create(['employee_id' => $employee->id, 'work_date' => today()->toDateString()]);
        $old->delete();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'work_date' => today()->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('attendance.restore', $old->id))
            ->assertSessionHasErrors('attendance');

        $this->assertSoftDeleted('attendances', ['id' => $old->id]);
    }
}