<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Filament\Pages\Printout;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Location;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Printout\EatFirstSection;
use App\Modules\Inventory\Printout\StockSection;
use App\Printout\SupplySection;
use App\Registries\PrintoutRegistry;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PrintoutTest extends TestCase
{
    use RefreshDatabase;

    private Household $household;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->seed(CategorySeeder::class);
        $this->household = Household::factory()->create(['name' => 'Home', 'target_days' => 10, 'eat_soon_days' => 30]);
        $this->household->setMemberCount(MemberType::Adult, 2);
        $this->household->setMemberCount(MemberType::Child, 1);
        $this->user = User::factory()->for($this->household)->create(['locale' => 'en']);
    }

    private function batch(string $name, int $quantity, ?string $bestBefore, ?Location $location = null, string $category = 'grains'): Batch
    {
        $product = Product::factory()->for($this->household)->create([
            'name' => $name,
            'brand' => null,
            'category_id' => Category::query()->where('key', $category)->value('id'),
            'package_value' => 500,
            'package_unit' => PackageUnit::Gram,
        ]);

        return Batch::factory()->for($product)->create(['quantity' => $quantity, 'best_before' => $bestBefore, 'location_id' => $location?->id, 'note' => null]);
    }

    public function test_core_and_modules_register_their_sections_in_order(): void
    {
        $sections = array_map(fn ($section): string => $section::class, app(PrintoutRegistry::class)->sections());

        $this->assertSame([SupplySection::class, EatFirstSection::class, StockSection::class], $sections);
    }

    public function test_the_printout_shows_supply_eat_first_and_stock_by_location(): void
    {
        $cellar = Location::factory()->for($this->household)->create(['name' => 'Cellar']);
        $attic = Location::factory()->for($this->household)->create(['name' => 'Attic']);
        $this->batch('Rice', 4, '2027-08-01', $cellar);
        $this->batch('Oats', 2, '2026-11-10', $cellar); // expired
        $this->batch('Beans', 3, '2026-12-01', $attic); // eat soon
        $this->batch('Crackers', 1, null);
        Batch::factory()->for(Product::factory()->create(['name' => 'Foreign']))->create(['best_before' => '2026-11-01']); // other household

        $this->actingAs($this->user)
            ->get(route('printout'))
            ->assertOk()
            ->assertSee('Emergency supply · Home')
            ->assertSee('As of 11/16/2026')
            ->assertSee('For 2 adults, 1 child.')
            ->assertSeeInOrder(['Supply', 'Drinks', '5.5 l', 'Eat first', 'Oats', 'Beans', 'Stock by location', 'Attic', 'Beans', 'Cellar', 'Oats', 'Rice', 'No location', 'Crackers'])
            ->assertSee('Taken')
            ->assertDontSee('Foreign');
    }

    public function test_the_printout_works_for_an_empty_household(): void
    {
        $this->household->setMemberCount(MemberType::Adult, 0);
        $this->household->setMemberCount(MemberType::Child, 0);

        $this->actingAs($this->user)
            ->get(route('printout'))
            ->assertOk()
            ->assertSee('Enter how many adults and children')
            ->assertSee('No batches yet.');
    }

    public function test_the_printout_is_in_the_language_of_the_person(): void
    {
        $this->user->update(['locale' => 'de']);

        $this->actingAs($this->user)
            ->get(route('printout'))
            ->assertOk()
            ->assertSee('Notvorrat')
            ->assertSee('Stand: 16.11.2026');
    }

    public function test_guests_are_sent_to_who_are_you(): void
    {
        $this->get(route('printout'))->assertRedirect(route('login'));
    }

    public function test_the_printout_page_links_to_the_printable_copy(): void
    {
        $this->actingAs($this->user)
            ->get(Printout::getUrl())
            ->assertOk()
            ->assertSee('Paper emergency copy')
            ->assertSee(route('printout'));
    }
}
