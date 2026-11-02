<?php
namespace Database\Factories;

use App\Models\User;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grossPay = $this->faker->randomFloat(2, 1000, 10000);
        $totalDeductions = $this->faker->randomFloat(2, 0, min(500, $grossPay));

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'user_id' => User::factory(),
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => round($grossPay - $totalDeductions, 2),
            'date_processed' => now(),
        ];
    }
}