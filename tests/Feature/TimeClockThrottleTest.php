<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimeClockThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $first = 'Jane', string $last = 'Doe'): Employee
    {
        return Employee::factory()->create([
            'first_name' => $first,
            'last_name' => $last,
            'suffix' => null,
            'pin' => Hash::make('123456'),
        ]);
    }

    private function punch(string $ip, string $name, string $pin): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('time-clock.store'), ['full_name' => $name, 'pin' => $pin, 'action' => 'time_in']);
    }

    private function pinError(): string
    {
        return (string) session('errors')?->first('pin');
    }

    public function test_one_ip_is_blocked_after_five_failed_attempts_even_with_the_right_pin(): void
    {
        $this->employee();

        for ($i = 0; $i < 5; $i++) {
            $this->punch('10.0.0.1', 'Jane Doe', '000000');
            $this->assertSame('Incorrect PIN.', $this->pinError());
        }

        $this->punch('10.0.0.1', 'Jane Doe', '123456');

        $this->assertStringContainsString('Too many unsuccessful attempts', $this->pinError());
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_rotating_ip_addresses_does_not_get_around_the_per_name_limit(): void
    {
        $this->employee();

        // 10 IP na tig-iisang mali lang: wala ni isa ang aabot sa per-IP limit
        for ($i = 1; $i <= 10; $i++) {
            $this->punch("10.0.1.{$i}", 'Jane Doe', '000000');
        }

        $this->punch('10.0.1.99', 'Jane Doe', '123456');

        $this->assertStringContainsString('Too many unsuccessful attempts', $this->pinError());
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_name_variations_share_the_same_limit(): void
    {
        $this->employee();

        foreach (['Jane Doe', 'jane doe', 'JANE  DOE', 'Jane   Doe', 'jane DOE'] as $index => $name) {
            $this->punch("10.0.2.{$index}", $name, '000000');
            $this->punch("10.0.3.{$index}", $name, '111111');
        }

        $this->punch('10.0.2.99', 'jane  doe', '123456');

        $this->assertStringContainsString('Too many unsuccessful attempts', $this->pinError());
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_a_locked_name_does_not_block_other_employees(): void
    {
        $this->employee();
        $this->employee('John', 'Smith');

        for ($i = 1; $i <= 10; $i++) {
            $this->punch("10.0.4.{$i}", 'Jane Doe', '000000');
        }

        $this->punch('10.0.4.99', 'John Smith', '123456');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_the_lock_ends_after_the_wait_time(): void
    {
        $this->employee();

        for ($i = 1; $i <= 10; $i++) {
            $this->punch("10.0.5.{$i}", 'Jane Doe', '000000');
        }

        $this->travel(11)->minutes();

        $this->punch('10.0.5.99', 'Jane Doe', '123456');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_correct_punches_and_duplicate_punch_errors_are_not_counted_as_failures(): void
    {
        $this->employee();

        for ($i = 1; $i <= 12; $i++) {
            $this->punch("10.0.6.{$i}", 'Jane Doe', '123456');
        }

        // isang time-in lang ang natala; ang 11 pang "already recorded" ay hindi bumilang
        $this->assertDatabaseCount('attendances', 1);
        $this->assertStringNotContainsString('Too many', $this->pinError());
    }
}