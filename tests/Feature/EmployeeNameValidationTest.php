<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNameValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_names_cannot_contain_numbers_or_special_characters(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->from(route('employees.create'))->post(route('employees.store'), [
            'first_name' => 'Alex1',
            'last_name' => 'Santos@',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.create'));
        $response->assertSessionHasErrors([
            'first_name' => 'The first name may only contain letters and spaces.',
            'last_name' => 'The last name may only contain letters and spaces.',
        ]);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_names_allow_accented_letters_and_spaces(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->post(route('employees.store'), [
            'first_name' => 'José',
            'last_name' => 'De la Cruz',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'first_name' => 'José',
            'last_name' => 'De la Cruz',
        ]);
    }

    public function test_daily_rate_rejects_special_characters_and_more_than_two_decimal_places(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        foreach (['₱500', '500e2', '500..00', '500.999'] as $dailyRate) {
            $response = $this->from(route('employees.create'))->post(route('employees.store'), [
                'first_name' => 'Alex',
                'last_name' => 'Santos',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'date_hired' => today()->toDateString(),
                'daily_rate' => $dailyRate,
                'status' => 'Regular',
            ]);

            $response->assertRedirect(route('employees.create'));
            $response->assertSessionHasErrors('daily_rate');
        }

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_daily_rate_accepts_amounts_with_up_to_two_decimal_places(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => '500.50',
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', ['daily_rate' => '500.50']);
    }
}
