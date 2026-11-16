<?php

namespace Tests\Feature;

use App\Filament\Pages\Printout;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Offline copy (#14): public/sw.js keeps the printout on the device. */
class OfflineCopyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->for(Household::factory())->create(['locale' => 'en']);
    }

    public function test_the_printout_is_marked_for_the_service_worker_and_not_cached_by_the_browser(): void
    {
        $response = $this->actingAs($this->user)->get(route('printout'))->assertOk();

        $response->assertHeader('X-Holdout-Printout', '1');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        // The banner the service worker switches on when it serves the copy offline.
        $response->assertSee('data-offline-banner hidden', false)->assertSee('Offline copy');
    }

    public function test_the_service_worker_keeps_the_printout_and_falls_back_to_it(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const COPY = '/printout'", $worker);
        $this->assertStringContainsString("'X-Holdout-Printout') !== '1'", $worker);
        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertStringContainsString("replace('data-offline-banner hidden', 'data-offline-banner')", $worker);
    }

    public function test_the_app_registers_the_service_worker_for_people_only(): void
    {
        $this->actingAs($this->user)->get('/')->assertOk()->assertSee("register('/sw.js'", false);
        auth()->logout();
        $this->get(route('login'))->assertDontSee("register('/sw.js'", false);
    }

    public function test_the_emergency_copy_page_shows_the_offline_copy_status(): void
    {
        $this->actingAs($this->user)
            ->get(Printout::getUrl())
            ->assertOk()
            ->assertSee('Emergency copy')
            ->assertSee('Offline copy on this device')
            ->assertSee('Update now');
    }
}
