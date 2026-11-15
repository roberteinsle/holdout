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

        $this->get('/who', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertSee('Wer bist du?');
        $this->get('/who', ['Accept-Language' => 'en-US'])->assertSee('Who are you?');
    }

    public function test_signed_in_users_get_their_household_language(): void
    {
        $user = User::factory()->for(Household::factory()->state(['locale' => 'de']))->create();

        $this->actingAs($user)
            ->get('/users', ['Accept-Language' => 'en-US'])
            ->assertOk()
            ->assertSee('Person hinzufügen');
    }
}
