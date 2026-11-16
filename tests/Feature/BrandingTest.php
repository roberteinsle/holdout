<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_brand_files_exist_and_the_manifest_lists_existing_icons(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'favicon-16.svg', 'apple-touch-icon.png', 'icon.svg', 'icon-maskable.svg', 'logo.svg', 'logo-dark.svg', 'mark.svg', 'mark-dark.svg'] as $file) {
            $this->assertFileExists(public_path($file));
        }

        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('#4A5342', $manifest['theme_color']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_the_guest_pages_show_the_logo_and_icons(): void
    {
        User::factory()->create();

        $this->get('/who')
            ->assertOk()
            ->assertSee('logo.svg')
            ->assertSee('logo-dark.svg')
            ->assertSee('apple-touch-icon.png')
            ->assertSee('manifest.json');
    }

    public function test_the_app_shows_the_logo_and_icons(): void
    {
        $this->actingAs(User::factory()->for(Household::factory())->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('logo.svg')
            ->assertSee('favicon.ico')
            ->assertSee('manifest.json');
    }
}
