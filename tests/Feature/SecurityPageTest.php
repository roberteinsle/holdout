<?php

namespace Tests\Feature;

use App\Filament\Pages\Security;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_passkey_sign_in_counts_as_recent_confirmation(): void
    {
        $request = Request::create('/passkeys/login', 'POST');
        $request->setLaravelSession($session = app('session.store'));

        app(PasskeyLoginResponse::class)->toResponse($request);

        $this->assertEqualsWithDelta(time(), $session->get('auth.password_confirmed_at'), 2);
    }

    public function test_with_an_old_sign_in_users_with_a_passkey_are_asked_to_confirm_with_it(): void
    {
        $user = User::factory()->create();
        $user->passkeys()->create(['name' => 'Phone', 'credential_id' => 'abc', 'credential' => []]);

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->call('startTotpSetup')
            ->assertDispatched('holdout-confirm-passkey', method: 'startTotpSetup', arguments: [])
            ->assertSet('totpQrCode', null);
    }

    public function test_with_an_old_sign_in_users_without_a_passkey_are_told_to_sign_in_again(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Security::class)
            ->call('startTotpSetup')
            ->assertNotDispatched('holdout-confirm-passkey')
            ->assertNotified();
    }

    public function test_the_dashboard_has_no_welcome_tile(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Alex Example']))
            ->get('/')
            ->assertOk()
            ->assertDontSee('fi-account-widget', false);
    }
}
