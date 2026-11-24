<?php

namespace Tests\Feature;

use App\Http\Requests\ListSearchRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SearchInputTest extends TestCase
{
    use RefreshDatabase;

    private function passes(string $value): bool
    {
        return Validator::make(['search' => $value], (new ListSearchRequest)->rules())->passes();
    }

    public function test_search_accepts_the_characters_employee_names_allow(): void
    {
        foreach (["O'Brien", 'Anne-Marie', 'Jr.', 'Dela Cruz', 'Muñoz'] as $value) {
            $this->assertTrue($this->passes($value), "{$value} should be accepted.");
        }
    }

    public function test_search_rejects_other_characters_and_a_trailing_newline(): void
    {
        foreach (['abc1', 'abc!', 'a%b', 'a_b', '<b>', "abc\n", str_repeat('a', 31)] as $value) {
            $this->assertFalse($this->passes($value), 'Search accepted '.json_encode($value));
        }
    }

    public function test_navbar_search_finds_names_with_punctuation(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Anne-Marie', 'last_name' => "O'Brien"]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('search', ['q' => "O'Br"]))
            ->assertOk()
            ->assertJsonPath('employees.0.id', $employee->id);
    }

    public function test_navbar_search_uses_the_same_rule_as_the_other_search_boxes(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['abc1', 'a%b', str_repeat('a', 31)] as $bad) {
            $this->getJson(route('search', ['q' => $bad]))->assertStatus(422)->assertJsonValidationErrors('q');
        }

        $this->getJson(route('search', ['q' => str_repeat('a', 30)]))->assertOk();
    }
}