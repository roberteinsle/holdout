<?php

namespace Tests\Feature;

use App\Enums\MemberType;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use App\Registries\StockAmount;
use App\Registries\StockRegistry;
use App\Registries\StockSource;
use App\Support\StockLevel;
use App\Support\SupplyStatus;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupplyStatusTest extends TestCase
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

    private function product(string $category, float $value, PackageUnit $unit): Product
    {
        return Product::factory()->for($this->household)->create([
            'category_id' => Category::query()->where('key', $category)->value('id'),
            'package_value' => $value,
            'package_unit' => $unit,
        ]);
    }

    public function test_the_inventory_module_reports_stock_per_category_through_the_registry(): void
    {
        $water = $this->product('water', 1.5, PackageUnit::Litre);
        $pasta = $this->product('grains', 500, PackageUnit::Gram);
        $oats = $this->product('grains', 1, PackageUnit::Kilogram);
        Batch::factory()->for($water)->create(['quantity' => 6, 'best_before' => '2027-06-01']);
        Batch::factory()->for($pasta)->create(['quantity' => 3, 'best_before' => null]);
        Batch::factory()->for($oats)->create(['quantity' => 1, 'best_before' => '2026-11-16']); // today: still usable
        Batch::factory()->for($oats)->create(['quantity' => 2, 'best_before' => '2026-11-15']); // expired
        Batch::factory()->for(Product::factory()->create())->create(['quantity' => 99]); // other household

        $stock = app(StockRegistry::class)->for($this->household);

        $this->assertEqualsWithDelta(9.0, $stock['water']->usable, 0.0001);
        $this->assertEqualsWithDelta(2.5, $stock['grains']->usable, 0.0001);
        $this->assertEqualsWithDelta(2.0, $stock['grains']->expired, 0.0001);
        $this->assertArrayNotHasKey('fruit', $stock);
    }

    public function test_stock_is_compared_with_the_target(): void
    {
        $water = $this->product('water', 1.5, PackageUnit::Litre);
        $pasta = $this->product('grains', 500, PackageUnit::Gram);
        Batch::factory()->for($water)->create(['quantity' => 14]); // 21 l of 20 l
        Batch::factory()->for($pasta)->create(['quantity' => 3]);  // 1.5 kg of 3.3 kg

        $status = app(SupplyStatus::class)->for($this->household);

        $this->assertSame(StockLevel::Enough, $status['water']->level());
        $this->assertSame(100, $status['water']->percent());
        $this->assertSame(10, $status['water']->days());
        $this->assertSame(0.0, $status['water']->missing());

        $this->assertSame(StockLevel::Low, $status['grains']->level());
        $this->assertSame(45, $status['grains']->percent());
        $this->assertSame(4, $status['grains']->days());
        $this->assertEqualsWithDelta(1.8, $status['grains']->missing(), 0.0001);

        $this->assertSame(StockLevel::Missing, $status['fruit']->level());
        $this->assertArrayNotHasKey('other', $status->all());

        // The supply lasts as long as the category that runs out first.
        $this->assertSame(0, SupplyStatus::daysOfSupply($status));
        $this->assertSame('vegetables', SupplyStatus::limitedBy($status)->target->category->key);
    }

    public function test_days_of_supply_is_the_smallest_category(): void
    {
        app(StockRegistry::class)->register('test', new class implements StockSource
        {
            public function stockByCategory(Household $household): array
            {
                // 5 days of everything, 2 days of fats.
                return collect(['water' => 10, 'grains' => 1.65, 'vegetables' => 2, 'fruit' => 1.25, 'dairy' => 1.25, 'protein' => 0.6, 'fats' => 0.066])
                    ->map(fn (float $amount): StockAmount => new StockAmount($amount))
                    ->all();
            }
        });

        $status = app(SupplyStatus::class)->for($this->household);

        $this->assertSame(5, $status['water']->days());
        $this->assertSame(2, SupplyStatus::daysOfSupply($status));
        $this->assertSame('fats', SupplyStatus::limitedBy($status)->target->category->key);
    }

    public function test_the_household_page_shows_stock_against_target(): void
    {
        $water = $this->product('water', 1.5, PackageUnit::Litre);
        $oats = $this->product('grains', 1, PackageUnit::Kilogram);
        Batch::factory()->for($water)->create(['quantity' => 14]);
        Batch::factory()->for($oats)->create(['quantity' => 2, 'best_before' => '2026-01-01']);

        $this->actingAs(User::factory()->for($this->household)->create());

        $this->get('/household')
            ->assertOk()
            ->assertSee('Your supply lasts 0 of 10 days.')
            ->assertSee('Runs out first: Grains, bread, potatoes')
            ->assertSeeInOrder(['Drinks', '21 l', '/ 20 l', 'Enough', '10 days'])
            ->assertSeeInOrder(['Grains, bread, potatoes', '0 kg', '/ 3.3 kg', 'Missing', '0 days', '3.3 kg missing'])
            ->assertSee('Plus 2 kg expired, not counted.');
    }
}
