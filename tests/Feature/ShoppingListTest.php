<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Filament\Pages\ShoppingListPage;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use App\Support\ShoppingList;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShoppingListTest extends TestCase
{
    use RefreshDatabase;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->seed(CategorySeeder::class);
        // 1 adult, 10 days: drinks 2 l/day = 20 l, grains 0.33 kg/day = 3.3 kg.
        $this->household = Household::factory()->create(['target_days' => 10]);
        $this->household->setMemberCount(MemberType::Adult, 1);
        $this->household->setMemberCount(MemberType::Child, 0);
    }

    private function stock(string $category, float $value, PackageUnit $unit, int $quantity, ?string $bestBefore = null): void
    {
        $product = Product::factory()->for($this->household)->create([
            'category_id' => Category::query()->where('key', $category)->value('id'),
            'package_value' => $value,
            'package_unit' => $unit,
        ]);
        Batch::factory()->for($product)->create(['quantity' => $quantity, 'best_before' => $bestBefore]);
    }

    public function test_it_lists_what_is_missing_per_category_rounded_up(): void
    {
        $this->stock('water', 1.5, PackageUnit::Litre, 14);   // 21 l of 20 l: enough
        $this->stock('grains', 500, PackageUnit::Gram, 3);    // 1.5 kg of 3.3 kg
        $this->stock('grains', 1, PackageUnit::Kilogram, 2, '2026-11-01'); // expired, not counted

        $items = app(ShoppingList::class)->for($this->household);

        $this->assertArrayNotHasKey('water', $items);
        $this->assertArrayNotHasKey('other', $items);
        $this->assertEqualsWithDelta(1.8, $items['grains']->amount, 0.0001);
        $this->assertSame(6, $items['grains']->daysShort); // lasts 4 of 10 days
        $this->assertEqualsWithDelta(2.0, $items['grains']->status->expired, 0.0001);
        $this->assertEqualsWithDelta(2.5, $items['fruit']->amount, 0.0001); // nothing at all: 0.25 kg × 10 days
    }

    public function test_amounts_are_rounded_up_without_float_noise(): void
    {
        $this->assertSame(3.3, ShoppingList::roundUp(3.3000000000000003));
        $this->assertSame(1.9, ShoppingList::roundUp(1.81));
        $this->assertSame(0.1, ShoppingList::roundUp(0.001));
        $this->assertSame(0.0, ShoppingList::roundUp(0.0));
    }

    public function test_the_page_shows_the_list_and_a_text_to_share(): void
    {
        $this->stock('grains', 500, PackageUnit::Gram, 3);
        $this->actingAs(User::factory()->for($this->household)->create(['locale' => 'en']));

        $this->get(ShoppingListPage::getUrl())
            ->assertOk()
            ->assertSeeInOrder(['Drinks', '20 l', 'Have 0 l of 20 l', '10 days short', 'Grains', '1.8 kg'])
            ->assertSee("Shopping list for 10 days\n- Drinks: 20 l", false);
    }

    public function test_a_full_supply_needs_nothing(): void
    {
        $this->household->update(['target_days' => 0]);
        $this->actingAs(User::factory()->for($this->household)->create(['locale' => 'en']));

        $this->get(ShoppingListPage::getUrl())
            ->assertOk()
            ->assertSee('Nothing to buy');
        $this->assertNull(ShoppingListPage::getNavigationBadge());
    }

    public function test_the_navigation_badge_counts_categories_with_something_missing(): void
    {
        $this->actingAs(User::factory()->for($this->household)->create());

        $this->assertSame((string) app(ShoppingList::class)->for($this->household)->count(), ShoppingListPage::getNavigationBadge());
        $this->get('/')->assertSee('Shopping list');
    }
}
