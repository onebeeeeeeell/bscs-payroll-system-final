<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNameValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_names_cannot_contain_numbers(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->from(route('employees.create'))->post(route('employees.store'), [
            'first_name' => 'Alex1',
            'last_name' => 'Santos2',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.create'));
        $response->assertSessionHasErrors([
            'first_name' => 'Numbers are not allowed in the first name.',
            'last_name' => 'Numbers are not allowed in the last name.',
        ]);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_names_can_include_non_numeric_characters(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->post(route('employees.store'), [
            'first_name' => 'José',
            'last_name' => "O'Neil",
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'first_name' => 'José',
            'last_name' => "O'Neil",
        ]);
    }
}
