<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DepartmentService;
use App\Services\EmploymentStatusService;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceInputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_service_normalizes_text_and_discards_unsupported_fields(): void
    {
        $department = app(DepartmentService::class)->create([
            'department_name' => ' <b>Human</b> Resources ',
            'id' => 999,
            'employees_count' => 100,
        ]);

        $this->assertSame('Human Resources', $department->department_name);
        $this->assertNotSame(999, $department->id);
        $this->assertArrayNotHasKey('employees_count', $department->getAttributes());
    }

    public function test_other_text_services_strip_tags_and_trim_values(): void
    {
        $position = app(PositionService::class)->create([
            'title' => ' <b>Lead</b> Operator ',
        ]);
        $employmentStatus = app(EmploymentStatusService::class)->create([
            'name' => ' <em>Seasonal</em> ',
        ]);

        $this->assertSame('Lead Operator', $position->title);
        $this->assertSame('Seasonal', $employmentStatus->name);
    }

    public function test_attendance_service_discards_client_calculated_fields(): void
    {
        $employee = Employee::factory()->create();

        $attendance = app(AttendanceService::class)->create([
            'employee_id' => $employee->id,
            'work_date' => today()->toDateString(),
            'time_in' => '08:00',
            'time_out' => '17:00',
            'hours_worked' => 999,
            'overtime_hours' => 999,
        ]);

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertSame('9.00', $attendance->hours_worked);
        $this->assertSame('1.00', $attendance->overtime_hours);
    }

    public function test_contribution_rate_edit_page_loads_type_and_usage_from_service_data(): void
    {
        $this->actingAs(User::factory()->create());
        $type = ContributionType::factory()->create(['name' => 'Health']);
        $rate = ContributionRate::factory()->create(['contribution_type_id' => $type->id]);

        $this->get(route('contribution_rates.edit', $rate->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('contribution-rate/edit')
                ->where('contributionRate.id', $rate->id)
                ->where('contributionRate.contribution_type.name', 'Health')
                ->where('locked', false)
            );
    }

    public function test_contribution_rate_list_marks_the_latest_rate_as_current(): void
    {
        $this->actingAs(User::factory()->create());
        $type = ContributionType::factory()->create();
        ContributionRate::factory()->create(['contribution_type_id' => $type->id]);
        $currentRate = ContributionRate::factory()->create(['contribution_type_id' => $type->id]);

        $this->get(route('contribution_rates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('contributionRates.data.0.id', $currentRate->id)
                ->where('contributionRates.data.0.is_current', true)
            );
    }
}