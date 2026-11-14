<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_their_browser_language(): void
    {
        User::factory()->create();

        $this->get('/login', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertSee('Mit Passkey anmelden');
        $this->get('/login', ['Accept-Language' => 'en-US'])->assertSee('Sign in with passkey');
    }

    public function test_signed_in_users_get_their_household_language(): void
    {
        $user = User::factory()->for(Household::factory()->state(['locale' => 'de']))->create();

        $this->actingAs($user)
            ->get('/security', ['Accept-Language' => 'en-US'])
            ->assertOk()
            ->assertSee('Deine Passkeys');
    }
}
