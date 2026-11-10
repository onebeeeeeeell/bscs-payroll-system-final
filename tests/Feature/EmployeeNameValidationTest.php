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

    public function test_suffix_rejects_numbers_and_special_characters_when_creating_or_editing(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $employee = Employee::factory()->create([
            'position_id' => $position->id,
            'department_id' => $department->id,
            'suffix' => null,
        ]);

        foreach (['Jr.2', 'III!'] as $suffix) {
            $createResponse = $this->from(route('employees.create'))->post(route('employees.store'), [
                'first_name' => 'Alex',
                'last_name' => 'Santos',
                'suffix' => $suffix,
                'position_id' => $position->id,
                'department_id' => $department->id,
                'date_hired' => today()->toDateString(),
                'daily_rate' => 500,
                'status' => 'Regular',
            ]);

            $createResponse->assertRedirect(route('employees.create'))
                ->assertSessionHasErrors([
                    'suffix' => 'The suffix may only contain letters and spaces.',
                ]);

            $this->patch(route('employees.update', $employee), ['suffix' => $suffix])
                ->assertSessionHasErrors([
                    'suffix' => 'The suffix may only contain letters and spaces.',
                ]);
        }

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'suffix' => null]);
    }

    public function test_suffix_accepts_letters_within_the_three_character_limit(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();

        $response = $this->post(route('employees.store'), [
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'suffix' => 'Jr',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
            'daily_rate' => 500,
            'status' => 'Regular',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', ['suffix' => 'Jr']);
    }

    public function test_hire_date_must_be_within_the_current_year_when_creating_or_editing(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $employee = Employee::factory()->create([
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => today()->toDateString(),
        ]);
        $nextYearDate = now()->addYear()->startOfYear()->toDateString();
        $employeeData = [
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'date_hired' => $nextYearDate,
            'daily_rate' => 500,
            'status' => 'Regular',
        ];

        $this->from(route('employees.create'))
            ->post(route('employees.store'), $employeeData)
            ->assertRedirect(route('employees.create'))
            ->assertSessionHasErrors([
                'date_hired' => 'The hire date must be within the current year.',
            ]);

        $this->patch(route('employees.update', $employee), ['date_hired' => $nextYearDate])
            ->assertSessionHasErrors([
                'date_hired' => 'The hire date must be within the current year.',
            ]);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'date_hired' => today()->toDateString(),
        ]);
    }

    public function test_name_and_suffix_length_limits_are_enforced_on_create_and_edit(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $employee = Employee::factory()->create([
            'position_id' => $position->id,
            'department_id' => $department->id,
        ]);
        $overLimitValues = [
            'first_name' => str_repeat('A', 51),
            'last_name' => str_repeat('B', 21),
            'suffix' => 'IIII',
        ];

        foreach ($overLimitValues as $field => $value) {
            $createData = [
                'first_name' => 'Alex',
                'last_name' => 'Santos',
                'position_id' => $position->id,
                'department_id' => $department->id,
                'date_hired' => today()->toDateString(),
                'daily_rate' => 500,
                'status' => 'Regular',
                $field => $value,
            ];

            $this->from(route('employees.create'))
                ->post(route('employees.store'), $createData)
                ->assertRedirect(route('employees.create'))
                ->assertSessionHasErrors($field);

            $this->patch(route('employees.update', $employee), [$field => $value])
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('employees', 1);
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
