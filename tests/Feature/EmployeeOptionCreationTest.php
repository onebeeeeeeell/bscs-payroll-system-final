<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOptionCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_created_from_employee_form(): void
    {
        $response = $this->postJson(route('employee-options.departments.store'), [
            'department_name' => 'Operations',
        ]);

        $response->assertCreated()->assertJson([
            'label' => 'Operations',
        ]);
        $this->assertDatabaseHas('departments', ['department_name' => 'Operations']);
    }

    public function test_position_can_be_created_from_employee_form(): void
    {
        $response = $this->postJson(route('employee-options.positions.store'), [
            'title' => 'Payroll Specialist',
        ]);

        $response->assertCreated()->assertJson([
            'label' => 'Payroll Specialist',
        ]);
        $this->assertDatabaseHas('positions', ['title' => 'Payroll Specialist']);
    }

    public function test_new_department_position_and_status_names_reject_numbers_and_special_characters(): void
    {
        $invalidDepartmentNames = ['Operations 2', 'People & Culture'];
        $invalidPositionNames = ['Payroll Analyst 2', 'Payroll-Analyst'];
        $invalidStatusNames = ['Contractual 2', 'Contractual!'];

        foreach ($invalidDepartmentNames as $name) {
            $this->postJson(route('employee-options.departments.store'), ['department_name' => $name])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('department_name');
            $this->assertDatabaseMissing('departments', ['department_name' => $name]);
        }

        foreach ($invalidPositionNames as $name) {
            $this->postJson(route('employee-options.positions.store'), ['title' => $name])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('title');
            $this->assertDatabaseMissing('positions', ['title' => $name]);
        }

        foreach ($invalidStatusNames as $name) {
            $this->postJson(route('employee-options.statuses.store'), ['name' => $name])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('name');
            $this->assertDatabaseMissing('employment_statuses', ['name' => $name]);
        }
    }

    public function test_custom_employment_status_can_be_created_and_assigned_to_an_employee(): void
    {
        $response = $this->postJson(route('employee-options.statuses.store'), [
            'name' => 'Contractual',
        ]);

        $response->assertCreated()->assertJson([
            'label' => 'Contractual',
        ]);
        $status = EmploymentStatus::where('name', 'Contractual')->firstOrFail();
        $employee = Employee::factory()->create(['status' => $status->name]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'Contractual',
        ]);
    }

    public function test_create_and_edit_forms_receive_employment_status_options(): void
    {
        $employee = Employee::factory()->create();

        foreach ([route('employees.create'), route('employees.edit', $employee)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('statuses', 3)
                    ->where('statuses.0.name', 'Casual')
                );
        }
    }

    public function test_department_and_position_cannot_be_deleted_when_employees_use_them(): void
    {
        $employee = Employee::factory()->create();

        $this->deleteJson(route('employee-options.departments.destroy', $employee->department))
            ->assertStatus(409)
            ->assertJsonPath('message', 'This department cannot be deleted because employee records are using it.');
        $this->deleteJson(route('employee-options.positions.destroy', $employee->position))
            ->assertStatus(409)
            ->assertJsonPath('message', 'This position cannot be deleted because employee records are using it.');

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_unused_department_and_position_can_be_deleted(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $this->deleteJson(route('employee-options.departments.destroy', $department))
            ->assertOk()
            ->assertJson(['deleted' => true]);
        $this->deleteJson(route('employee-options.positions.destroy', $position))
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_department_and_position_can_be_renamed_without_changing_employee_assignments(): void
    {
        $employee = Employee::factory()->create();

        $this->patchJson(route('employee-options.departments.update', $employee->department), [
            'department_name' => 'People Operations',
        ])->assertOk()->assertJson(['label' => 'People Operations']);

        $this->patchJson(route('employee-options.positions.update', $employee->position), [
            'title' => 'Payroll Analyst',
        ])->assertOk()->assertJson(['label' => 'Payroll Analyst']);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
        ]);
        $this->assertDatabaseHas('departments', ['id' => $employee->department_id, 'department_name' => 'People Operations']);
        $this->assertDatabaseHas('positions', ['id' => $employee->position_id, 'title' => 'Payroll Analyst']);
    }

    public function test_renamed_department_position_and_status_reject_numbers_and_special_characters(): void
    {
        $employee = Employee::factory()->create();
        $status = EmploymentStatus::where('name', 'Casual')->firstOrFail();

        $this->patchJson(route('employee-options.departments.update', $employee->department), [
            'department_name' => 'People & Culture',
        ])->assertUnprocessable()->assertJsonValidationErrors('department_name');
        $this->patchJson(route('employee-options.positions.update', $employee->position), [
            'title' => 'Payroll Analyst 2',
        ])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->patchJson(route('employee-options.statuses.update', $status), [
            'name' => 'Temporary/Seasonal',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_status_can_be_renamed_and_employee_records_are_updated(): void
    {
        $status = EmploymentStatus::where('name', 'Casual')->firstOrFail();
        $employee = Employee::factory()->create(['status' => $status->name]);

        $this->patchJson(route('employee-options.statuses.update', $status), ['name' => 'Temporary'])
            ->assertOk()
            ->assertJson(['label' => 'Temporary']);

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'Temporary']);
    }

    public function test_status_cannot_be_deleted_while_employee_records_use_it(): void
    {
        $status = EmploymentStatus::where('name', 'Regular')->firstOrFail();
        Employee::factory()->create(['status' => $status->name]);

        $this->deleteJson(route('employee-options.statuses.destroy', $status))
            ->assertStatus(409)
            ->assertJsonPath('message', 'This status cannot be deleted because employee records are using it.');
    }

    public function test_unused_status_can_be_deleted(): void
    {
        $status = EmploymentStatus::create(['name' => 'Archived']);

        $this->deleteJson(route('employee-options.statuses.destroy', $status))
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('employment_statuses', ['id' => $status->id]);
    }
}
