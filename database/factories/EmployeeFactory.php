<?php
namespace Database\Factories;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'suffix' => null,
            'position_id' => Position::factory(),
            'department_id' => Department::factory(),
            'date_hired' => now()->toDateString(),
            'daily_rate' => $this->faker->randomFloat(2, 300, 1500),
            'sss_no' => null,
            'philhealth_no' => null,
            'pagibig_no' => null,
            'tin_no' => null,
            'status' => $this->faker->randomElement(['Casual', 'Regular', 'Permanent']),
        ];
    }
}