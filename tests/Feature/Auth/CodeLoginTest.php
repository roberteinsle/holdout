<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Security;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class CodeLoginTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->google2fa = new Google2FA;
    }

    private function userWithTotp(): array
    {
        $secret = $this->google2fa->generateSecretKey(32);
        $user = User::factory()->create();
        $user->forceFill([
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
            'totp_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
        ])->save();

        return [$user, $secret];
    }

    public function test_setup_on_the_security_page_stores_an_encrypted_secret_and_shows_recovery_codes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        $page = Livewire::test(Security::class)
            ->call('startTotpSetup')
            ->assertSet('totpQrCode', fn ($qr) => str_starts_with($qr, 'data:image/svg+xml;base64,'));

        $secret = session('totp.pending_secret');

        $page->set('totpCode', '000000')->call('confirmTotpSetup')->assertHasErrors('totpCode');

        $page->set('totpCode', $this->google2fa->getCurrentOtp($secret))
            ->call('confirmTotpSetup')
            ->assertHasNoErrors()
            ->assertCount('recoveryCodes', 8);

        $user->refresh();
        $this->assertTrue($user->hasTotpEnabled());
        $this->assertSame($secret, $user->totp_secret);
        $this->assertStringNotContainsString($secret, (string) $user->getRawOriginal('totp_secret'));
    }

    public function test_setup_needs_a_recent_sign_in(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Security::class)->call('startTotpSetup')->assertSet('totpQrCode', null);
        $this->assertNull(session('totp.pending_secret'));
    }

    public function test_email_and_current_code_sign_in(): void
    {
        [$user, $secret] = $this->userWithTotp();

        $this->post('/login/code', ['email' => $user->email, 'code' => $this->google2fa->getCurrentOtp($secret)])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        [$user, $secret] = $this->userWithTotp();
        $code = $this->google2fa->getCurrentOtp($secret);

        $this->post('/login/code', ['email' => $user->email, 'code' => $code]);
        auth()->logout();

        $this->post('/login/code', ['email' => $user->email, 'code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_codes_work_once(): void
    {
        [$user] = $this->userWithTotp();

        $this->post('/login/code', ['email' => $user->email, 'code' => 'aaaa-bbbb'])->assertRedirect('/');
        auth()->logout();

        $this->post('/login/code', ['email' => $user->email, 'code' => 'AAAA-BBBB'])->assertSessionHasErrors('code');
        $this->assertSame(['CCCC-DDDD'], $user->refresh()->totp_recovery_codes);
    }

    public function test_wrong_code_unknown_address_and_no_app_give_the_same_error(): void
    {
        [$user] = $this->userWithTotp();
        $withoutApp = User::factory()->create();

        $messages = collect([
            ['email' => $user->email, 'code' => '123456'],
            ['email' => 'nobody@example.com', 'code' => '123456'],
            ['email' => $withoutApp->email, 'code' => '123456'],
        ])->map(fn ($input) => $this->post('/login/code', $input)->assertSessionHasErrors('code')->getSession()->get('errors')->first('code'));

        $this->assertCount(1, $messages->unique());
        $this->assertGuest();
    }

    public function test_code_sign_in_is_rate_limited_per_address(): void
    {
        [$user] = $this->userWithTotp();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/code', ['email' => $user->email, 'code' => '000000']);
        }

        $this->post('/login/code', ['email' => $user->email, 'code' => '000000'])->assertStatus(429);
    }
}
