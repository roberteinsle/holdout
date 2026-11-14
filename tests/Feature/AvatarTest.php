<?php

namespace Tests\Feature;

use App\Filament\Support\InitialsAvatarProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_do_not_load_avatars_from_third_parties(): void
    {
        $user = User::factory()->create(['name' => 'Alex Example']);

        $this->actingAs($user)->get('/security')->assertOk()->assertDontSee('ui-avatars.com');
    }

    public function test_the_avatar_shows_escaped_initials(): void
    {
        $svg = base64_decode(str((new InitialsAvatarProvider)->get(new User(['name' => '<b>ob Example'])))->after('base64,'));

        $this->assertStringContainsString('>BE</text>', $svg);
        $this->assertStringNotContainsString('<b>', $svg);
    }
}
