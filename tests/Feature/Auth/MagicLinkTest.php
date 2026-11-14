<?php

namespace Tests\Feature\Auth;

use App\Mail\MagicLinkMail;
use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function requestLinkFor(User $user): string
    {
        $this->post('/login', ['email' => $user->email])->assertRedirect(route('login.sent'));

        $url = null;
        Mail::assertQueued(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$url) {
            $url = $mail->url;

            return true;
        });

        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        User::factory()->create();

        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in with passkey');
    }

    public function test_a_known_address_gets_a_link_that_signs_in_once(): void
    {
        $user = User::factory()->create();
        $token = $this->requestLinkFor($user);

        $this->assertDatabaseMissing('login_tokens', ['token_hash' => $token]);
        $this->assertDatabaseHas('login_tokens', ['token_hash' => hash('sha256', $token)]);

        // Opening the link alone does not sign in (mail scanners prefetch links).
        $this->get(route('login.confirm', $token))->assertOk()->assertSee('Sign in now');
        $this->assertGuest();

        $this->post(route('login.consume', $token))
            ->assertRedirect(route('filament.app.pages.security', ['welcome' => 1]));
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        $this->post(route('login.consume', $token))->assertRedirect(route('login.confirm', $token));
        $this->assertGuest();
        $this->get(route('login.confirm', $token))->assertSee('This link no longer works');
    }

    public function test_an_unknown_address_gets_the_same_answer_and_no_mail(): void
    {
        User::factory()->create();

        $this->post('/login', ['email' => 'nobody@example.com'])->assertRedirect(route('login.sent'));

        Mail::assertNothingQueued();
        $this->assertDatabaseCount('login_tokens', 0);
    }

    public function test_links_expire_after_15_minutes(): void
    {
        $user = User::factory()->create();
        $token = $this->requestLinkFor($user);

        $this->travel(LoginToken::LIFETIME_MINUTES + 1)->minutes();

        $this->post(route('login.consume', $token))->assertRedirect(route('login.confirm', $token));
        $this->assertGuest();
    }

    public function test_requesting_links_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email])->assertRedirect();
        }

        $this->post('/login', ['email' => $user->email])->assertStatus(429);
    }

    /** @return array<string, array{string}> */
    public static function injectionPayloads(): array
    {
        return [
            'or true' => ["' OR '1'='1"],
            'comment' => ["admin'--"],
            'union' => ["x' UNION SELECT * FROM users--@example.com"],
        ];
    }

    #[DataProvider('injectionPayloads')]
    public function test_sql_injection_payloads_are_rejected_without_a_database_error(string $payload): void
    {
        User::factory()->create();

        $this->post('/login', ['email' => $payload])->assertSessionHasErrors('email');
        Mail::assertNothingQueued();
    }

    public function test_tampered_tokens_do_not_sign_in(): void
    {
        $user = User::factory()->create();
        $this->requestLinkFor($user);

        $this->post('/login/link/'.str_repeat('a', 64))->assertRedirect();
        $this->assertGuest();
        $this->post('/login/link/'.urlencode("' OR '1'='1"))->assertNotFound();
    }

    public function test_users_with_a_passkey_go_straight_to_the_dashboard(): void
    {
        $user = User::factory()->create();
        $user->passkeys()->create([
            'name' => 'Phone',
            'credential_id' => 'abc',
            'credential' => [],
        ]);
        $token = $this->requestLinkFor($user);

        $this->post(route('login.consume', $token))->assertRedirect('/');
    }

    public function test_magic_link_login_counts_as_a_recent_confirmation_for_passkey_setup(): void
    {
        $user = User::factory()->create();
        $token = $this->requestLinkFor($user);

        $this->post(route('login.consume', $token));

        $this->getJson('/user/passkeys/options')->assertOk()->assertJsonStructure(['options']);
    }
}
