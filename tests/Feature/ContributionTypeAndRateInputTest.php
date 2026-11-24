<?php

namespace Tests\Feature;

use App\Models\ContributionType;
use App\Models\User;
use App\Services\ContributionTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContributionTypeAndRateInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_name_must_be_unique_ignoring_letter_case(): void
    {
        ContributionType::factory()->create(['name' => 'SSS']);

        foreach (['SSS', 'sss', 'Sss'] as $name) {
            $this->actingAs(User::factory()->create())
                ->post(route('contribution_types.store'), ['name' => $name])
                ->assertSessionHasErrors('name');
        }

        $this->assertDatabaseCount('contribution_types', 1);
    }

    public function test_type_name_allows_only_a_single_space_between_words(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('contribution_types.store'), ['name' => 'Home  Loan'])
            ->assertSessionHasErrors('name');

        $this->post(route('contribution_types.store'), ['name' => 'Home Loan'])
            ->assertRedirect(route('contribution_types.index'));

        $this->assertDatabaseHas('contribution_types', ['name' => 'Home Loan']);
    }

    public function test_type_update_can_keep_its_own_name_but_not_take_another(): void
    {
        $sss = ContributionType::factory()->create(['name' => 'SSS']);
        ContributionType::factory()->create(['name' => 'Health']);

        $this->actingAs(User::factory()->create())
            ->patch(route('contribution_types.update', $sss), ['name' => 'SSS'])
            ->assertSessionHasNoErrors();

        $this->patch(route('contribution_types.update', $sss), ['name' => 'health'])
            ->assertSessionHasErrors('name');

        $this->assertSame('SSS', $sss->fresh()->name);
    }

    public function test_service_turns_a_database_duplicate_into_a_validation_error(): void
    {
        ContributionType::factory()->create(['name' => 'SSS']);

        $this->expectException(ValidationException::class);

        // dumaan sa labas ng request, kaya ang unique index ang haharang
        app(ContributionTypeService::class)->create(['name' => 'sss']);
    }

    public function test_fixed_rate_cannot_exceed_the_gross_pay_limit(): void
    {
        $type = ContributionType::factory()->create();
        $this->actingAs(User::factory()->create());

        foreach (['30000.01', '99999999.99'] as $bad) {
            $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'fixed', 'rate' => $bad])
                ->assertSessionHasErrors('rate');
        }

        $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'fixed', 'rate' => '30000.00'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contribution_rates.index'));

        $this->assertDatabaseCount('contribution_rates', 1);
    }

    public function test_percent_rate_still_caps_at_100(): void
    {
        $type = ContributionType::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'percent', 'rate' => '100.01'])
            ->assertSessionHasErrors('rate');

        $this->post(route('contribution_rates.store'), ['contribution_type_id' => $type->id, 'calculation_type' => 'percent', 'rate' => '100'])
            ->assertSessionHasNoErrors();
    }
}