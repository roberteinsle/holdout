<?php

namespace Tests\Feature\Auth;

use App\Enums\MemberType;
use App\Enums\Role;
use App\Mail\MagicLinkMail;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_installation_sends_people_to_registration(): void
    {
        $this->get('/login')->assertRedirect('/register');
        $this->get('/register')->assertOk()->assertSee('Set up your household');
    }

    public function test_the_first_person_creates_the_household_and_becomes_owner(): void
    {
        Mail::fake();

        $this->post('/register', [
            'household' => 'The Millers',
            'name' => 'Sam Miller',
            'email' => 'Sam@Example.com',
            'adults' => 2,
            'children' => 1,
        ])->assertRedirect(route('login.sent'));

        $user = User::query()->sole();
        $this->assertSame('sam@example.com', $user->email);
        $this->assertSame(Role::Owner, $user->role);
        $this->assertSame('The Millers', $user->household->name);
        $this->assertSame(2, $user->household->memberCount(MemberType::Adult));
        $this->assertSame(1, $user->household->memberCount(MemberType::Child));
        $this->assertGuest();
        Mail::assertQueued(MagicLinkMail::class, fn ($mail) => $mail->user->is($user));
    }

    public function test_registration_is_closed_once_a_household_exists(): void
    {
        Mail::fake();
        Household::factory()->create();

        $this->get('/register')->assertRedirect('/login');
        $this->post('/register', [
            'household' => 'Intruders',
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'adults' => 1,
            'children' => 0,
        ])->assertRedirect('/login');

        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseMissing('users', ['email' => 'eve@example.com']);
        Mail::assertNothingQueued();
    }

    public function test_registration_validates_input(): void
    {
        $this->post('/register', ['household' => '', 'name' => str_repeat('x', 101), 'email' => 'nope', 'adults' => 0, 'children' => -1])
            ->assertSessionHasErrors(['household', 'name', 'email', 'adults', 'children']);
    }
}
