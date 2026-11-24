<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Hindi kailangan ng built assets (npm run build) para sa mga page test dito
        $this->withoutVite();

        $this->actingAs(User::factory()->create());
    }

    public function test_archived_employee_cannot_be_opened_for_edit(): void
    {
        $employee = Employee::factory()->create(['archived_at' => now()]);

        $this->get(route('employees.edit', $employee))->assertNotFound();
    }

    public function test_archived_employee_cannot_be_updated_by_a_manual_request(): void
    {
        $employee = Employee::factory()->create(['archived_at' => now(), 'first_name' => 'Jane']);

        $this->patch(route('employees.update', $employee), ['first_name' => 'Changed'])->assertNotFound();

        $this->assertSame('Jane', $employee->fresh()->first_name);
    }

    public function test_active_employee_edit_page_gets_the_employee_and_form_options(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('employee/edit')
                ->where('employee.id', $employee->id)
                ->has('positions')
                ->has('departments')
                ->has('statuses'));
    }

    public function test_payroll_edit_page_loads_its_details(): void
    {
        $payroll = Payroll::factory()->create();

        $this->get(route('payrolls.edit', $payroll))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('payroll/edit')
                ->where('payroll.id', $payroll->id)
                ->has('payroll.employee')
                ->has('payroll.payroll_period'));
    }

    public function test_settings_forms_still_validate_through_their_form_requests(): void
    {
        $this->put(route('password.update'), [])->assertSessionHasErrors(['current_password', 'password']);

        $this->delete(route('profile.destroy'), ['password' => 'wrong-password'])->assertSessionHasErrors('password');
    }
}