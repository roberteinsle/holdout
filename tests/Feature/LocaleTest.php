<?php

namespace Tests\Feature;

use App\Filament\Pages\Profile;
use App\Models\Household;
use App\Models\User;
use App\Registries\CategoryRegistry;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function guestLanguages(): array
    {
        return [
            'English' => ['en-US,en;q=0.9', 'Sign in with passkey'],
            'German' => ['de-DE,de;q=0.9', 'Mit Passkey anmelden'],
            'Spanish' => ['es-ES,es;q=0.9', 'Iniciar sesión con passkey'],
            'Portuguese' => ['pt-PT,pt;q=0.9', 'Iniciar sessão com passkey'],
            'Brazilian Portuguese' => ['pt-BR,pt;q=0.9', 'Iniciar sessão com passkey'],
            'French' => ['fr-FR,fr;q=0.9', 'Se connecter avec une passkey'],
            'unsupported falls back to English' => ['it-IT,it;q=0.9', 'Sign in with passkey'],
        ];
    }

    #[DataProvider('guestLanguages')]
    public function test_guests_get_their_browser_language(string $acceptLanguage, string $expected): void
    {
        User::factory()->create();

        $this->get('/login', ['Accept-Language' => $acceptLanguage])->assertOk()->assertSee($expected);
    }

    public function test_people_get_the_language_from_their_profile(): void
    {
        $user = User::factory()->owner()->create(['locale' => 'fr']);

        $this->actingAs($user)
            ->get('/users', ['Accept-Language' => 'de-DE'])
            ->assertOk()
            ->assertSee('Inviter');
    }

    public function test_without_a_chosen_language_the_browser_decides(): void
    {
        $user = User::factory()->owner()->create(['locale' => null]);

        $this->actingAs($user)->get('/users', ['Accept-Language' => 'es-ES'])->assertSee('Invitar');
    }

    public function test_each_person_chooses_their_language_on_the_profile_page(): void
    {
        $household = Household::factory()->create();
        $alex = User::factory()->owner()->for($household)->create(['name' => 'Alex', 'locale' => 'en']);
        $sam = User::factory()->for($household)->create(['name' => 'Sam', 'locale' => 'en']);

        $this->actingAs($alex);
        $this->get('/profile')->assertOk()->assertSee('Language');

        Livewire::test(Profile::class)
            ->assertSchemaStateSet(['name' => 'Alex', 'locale' => 'en'])
            ->fillForm(['name' => 'Alexandra', 'locale' => 'pt'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(Profile::getUrl());

        $this->assertSame('pt', $alex->fresh()->locale);
        $this->assertSame('Alexandra', $alex->fresh()->name);
        $this->assertSame('en', $sam->fresh()->locale);

        $this->actingAs($alex->fresh())->get('/users')->assertSee('Convidar');
    }

    public function test_only_supported_languages_can_be_chosen(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Profile::class)
            ->fillForm(['locale' => 'it'])
            ->call('save')
            ->assertHasFormErrors(['locale']);
    }

    public function test_validation_messages_appear_in_the_chosen_language(): void
    {
        $this->actingAs(User::factory()->create(['locale' => 'de']));
        app()->setLocale('de');

        Livewire::test(Profile::class)
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertSee('muss ausgefüllt werden');
    }

    public function test_registration_stores_the_browser_language_for_the_first_person(): void
    {
        Mail::fake();

        $this->withHeader('Accept-Language', 'fr-FR')
            ->post('/register', ['household' => 'Famille', 'name' => 'Camille', 'email' => 'camille@example.com', 'adults' => 2, 'children' => 0]);

        $this->assertSame('fr', User::query()->sole()->locale);
    }

    public function test_every_app_string_is_translated_into_every_language(): void
    {
        $german = json_decode(file_get_contents(lang_path('de.json')), true);

        foreach (array_diff(Locales::codes(), ['en']) as $locale) {
            $strings = json_decode(file_get_contents(lang_path("{$locale}.json")), true);

            foreach ($this->appStrings() as $key) {
                $this->assertArrayHasKey($key, $strings, "{$locale}.json misses: {$key}");
                $this->assertNotSame('', trim($strings[$key]), "{$locale}.json is empty for: {$key}");
            }
        }

        $this->assertNotEmpty($german);
    }

    /** @return list<string> All __('...') and trans_choice('...') strings in app/ and resources/views plus the category names. */
    private function appStrings(): array
    {
        $keys = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path('app'), \FilesystemIterator::SKIP_DOTS));
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));

        foreach ([$files, $views] as $iterator) {
            foreach ($iterator as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                preg_match_all("/(?:__|trans_choice)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file->getPathname()), $matches);
                foreach ($matches[1] as $key) {
                    $keys[] = stripslashes($key);
                }
            }
        }

        foreach (app(CategoryRegistry::class)->all() as $category) {
            $keys[] = $category->name;
        }

        return array_values(array_unique($keys));
    }
}
