<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Filament\Pages\HouseholdSettings;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseholdSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function signedIn(): User
    {
        $household = Household::factory()->create(['name' => 'The Millers', 'target_days' => 14, 'eat_soon_days' => 60]);
        $household->setMemberCount(MemberType::Adult, 2);
        $household->setMemberCount(MemberType::Child, 1);

        $user = User::factory()->for($household)->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_the_page_shows_the_current_settings(): void
    {
        $this->signedIn();

        $this->get('/household')->assertOk()->assertSee('Who is the supply for?');

        Livewire::test(HouseholdSettings::class)->assertSchemaStateSet([
            'name' => 'The Millers',
            'adults' => 2,
            'children' => 1,
            'target_days' => 14,
            'eat_soon_days' => 60,
        ]);
    }

    public function test_saving_updates_household_size_and_planning(): void
    {
        $user = $this->signedIn();

        Livewire::test(HouseholdSettings::class)
            ->fillForm(['name' => 'Millers', 'adults' => 3, 'children' => 0, 'target_days' => 10, 'eat_soon_days' => 30])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $household = $user->household->fresh();
        $this->assertSame('Millers', $household->name);
        $this->assertSame(3, $household->memberCount(MemberType::Adult));
        $this->assertSame(0, $household->memberCount(MemberType::Child));
        $this->assertSame(3, $household->memberTotal());
        $this->assertSame(10, $household->target_days);
        $this->assertSame(30, $household->eat_soon_days);
        $this->assertDatabaseCount('members', 2);
    }

    public function test_input_is_validated(): void
    {
        $this->signedIn();

        Livewire::test(HouseholdSettings::class)
            ->fillForm(['name' => '', 'adults' => 0, 'children' => -1, 'target_days' => 0, 'eat_soon_days' => 400])
            ->call('save')
            ->assertHasFormErrors(['name', 'adults', 'children', 'target_days', 'eat_soon_days']);
    }
}
