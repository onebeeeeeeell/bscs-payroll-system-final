<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_index_filters_by_department_position_hire_year_and_month(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $position = Position::factory()->create();
        $otherPosition = Position::factory()->create();

        $matchingEmployee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_hired' => '2024-05-15',
        ]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_hired' => '2024-06-15',
        ]);
        Employee::factory()->create([
            'department_id' => $otherDepartment->id,
            'position_id' => $position->id,
            'date_hired' => '2024-05-15',
        ]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $otherPosition->id,
            'date_hired' => '2024-05-15',
        ]);

        $response = $this->get(route('employees.index', [
            'department_id' => $department->id,
            'position_id' => $position->id,
            'hire_year' => 2024,
            'hire_month' => 5,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('employee/index')
            ->has('employees.data', 1)
            ->where('employees.data.0.id', $matchingEmployee->id)
            ->where('filters.department_id', (string) $department->id)
            ->where('filters.position_id', (string) $position->id)
            ->where('filters.hire_year', '2024')
            ->where('filters.hire_month', '5')
            ->where('hireYears.0', (int) now()->year)
        );
    }
}
