<?php
namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->startOfDay();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(13)->toDateString(),
            'pay_date' => $startDate->copy()->addDays(14)->toDateString(),
            'status' => 'Pending',
        ];
    }
}