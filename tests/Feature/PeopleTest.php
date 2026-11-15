<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Household;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_installation_leads_to_household_setup(): void
    {
        $this->get('/')->assertRedirect('/who');
        $this->get('/who')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertSee('Set up your household');
    }

    public function test_setup_creates_the_household_and_selects_the_first_person(): void
    {
        $this->post('/setup', ['household' => 'The Millers', 'name' => 'Alex', 'adults' => 2, 'children' => 1])->assertRedirect('/');

        $user = User::query()->sole();
        $this->assertSame('Alex', $user->name);
        $this->assertSame('The Millers', $user->household->name);
        $this->assertSame(2, $user->household->memberCount(MemberType::Adult));
        $this->assertSame(1, $user->household->memberCount(MemberType::Child));
        $this->assertSame(14, $user->household->target_days);
        $this->assertSame('en', $user->locale);
        $this->assertAuthenticatedAs($user);
    }

    public function test_setup_is_closed_once_a_household_exists(): void
    {
        Household::factory()->create();

        $this->get('/setup')->assertRedirect('/who');
        $this->post('/setup', ['household' => 'Second', 'name' => 'Eve', 'adults' => 1, 'children' => 0])->assertRedirect('/who');
        $this->assertDatabaseCount('households', 1);
    }

    public function test_setup_validates_input(): void
    {
        $this->post('/setup', ['household' => '', 'name' => str_repeat('x', 101), 'adults' => 0, 'children' => -1])
            ->assertSessionHasErrors(['household', 'name', 'adults', 'children']);
    }

    public function test_a_device_picks_who_is_using_it_and_keeps_it(): void
    {
        $household = Household::factory()->create();
        $alex = User::factory()->for($household)->create(['name' => 'Alex']);
        User::factory()->for($household)->create(['name' => 'Sam']);

        $this->get('/who')->assertOk()->assertSeeInOrder(['Who are you?', 'Alex', 'Sam']);

        $response = $this->post(route('people.select', $alex))->assertRedirect('/');
        $this->assertAuthenticatedAs($alex);

        // The remember-me cookie is set, so the choice survives closing the browser.
        $this->assertNotNull(collect($response->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_')));
    }

    public function test_new_people_can_add_themselves(): void
    {
        $household = Household::factory()->create();

        $this->post('/who', ['name' => 'Kim'])->assertRedirect('/');

        $kim = User::query()->where('name', 'Kim')->sole();
        $this->assertSame($household->id, $kim->household_id);
        $this->assertAuthenticatedAs($kim);
    }

    public function test_the_app_needs_no_sign_in_routes(): void
    {
        User::factory()->create();

        foreach (['/register', '/login', '/passkeys/login/options', '/login/code', '/security', '/invitations'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_the_family_page_adds_edits_and_removes_people(): void
    {
        $me = User::factory()->create(['name' => 'Alex']);
        $other = User::factory()->for($me->household)->create(['name' => 'Sam']);
        $stranger = User::factory()->create();

        $this->actingAs($me);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$me, $other])
            ->assertCanNotSeeTableRecords([$stranger])
            ->callAction('create', ['name' => 'Kim'])
            ->assertHasNoActionErrors()
            ->callAction(TestAction::make('edit')->table($other), ['name' => 'Samantha'])
            ->assertActionHidden(TestAction::make('delete')->table($me))
            ->callAction(TestAction::make('delete')->table($other));

        $this->assertDatabaseHas('users', ['name' => 'Kim', 'household_id' => $me->household_id]);
        $this->assertModelMissing($other);
    }

    public function test_switch_person_returns_to_the_selection(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('filament.app.auth.logout'))->assertRedirect();
        $this->assertGuest();
        $this->get('/')->assertRedirect('/who');
    }
}
