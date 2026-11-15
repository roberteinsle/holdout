<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Models\Household;
use App\Models\User;
use App\Support\SupplyTargets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyTargetsTest extends TestCase
{
    use RefreshDatabase;

    private function household(int $adults, int $children, int $days): Household
    {
        $household = Household::factory()->create(['target_days' => $days, 'locale' => 'en']);
        $household->setMemberCount(MemberType::Adult, $adults);
        $household->setMemberCount(MemberType::Child, $children);

        return $household;
    }

    public function test_targets_are_need_per_person_times_people_times_days(): void
    {
        $targets = app(SupplyTargets::class)->for($this->household(adults: 2, children: 1, days: 14));

        // Drinks: (2 × 2.0 l + 1 × 1.5 l) × 14 days
        $this->assertEqualsWithDelta(5.5, $targets['water']->perDay, 0.0001);
        $this->assertEqualsWithDelta(77.0, $targets['water']->total, 0.0001);

        // Grains: children count like adults: 3 × 0.33 kg × 14 days
        $this->assertEqualsWithDelta(0.99, $targets['grains']->perDay, 0.0001);
        $this->assertEqualsWithDelta(13.86, $targets['grains']->total, 0.0001);

        // Fats: 3 × 0.033 kg × 14 days
        $this->assertEqualsWithDelta(1.386, $targets['fats']->total, 0.0001);

        $this->assertSame(['water', 'grains', 'vegetables', 'fruit', 'dairy', 'protein', 'fats'], $targets->keys()->all());
    }

    public function test_a_different_number_of_days_can_be_asked_for(): void
    {
        $targets = app(SupplyTargets::class)->for($this->household(adults: 1, children: 0, days: 14), days: 10);

        $this->assertEqualsWithDelta(20.0, $targets['water']->total, 0.0001);
        $this->assertEqualsWithDelta(3.3, $targets['grains']->total, 0.0001);
    }

    public function test_an_empty_household_needs_nothing(): void
    {
        $targets = app(SupplyTargets::class)->for($this->household(adults: 0, children: 0, days: 14));

        $this->assertSame(0.0, $targets->sum(fn ($target) => $target->total));
    }

    public function test_the_household_page_shows_the_targets(): void
    {
        $household = $this->household(adults: 2, children: 1, days: 14);
        $this->actingAs(User::factory()->for($household)->create());

        $this->get('/household')
            ->assertOk()
            ->assertSee('What you need for 14 days')
            ->assertSeeInOrder(['Drinks', '5.5 l', '77 l', 'Grains, bread, potatoes', '1 kg', '13.9 kg']);
    }

    public function test_the_household_page_asks_for_the_size_when_it_is_missing(): void
    {
        $household = Household::factory()->create(['locale' => 'en']);
        $this->actingAs(User::factory()->for($household)->create());

        $this->get('/household')->assertOk()->assertSee('Enter how many adults and children live in the household');
    }
}
