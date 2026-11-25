<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Filament\Pages\Scan;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Location;
use App\Modules\Inventory\Models\Movement;
use App\Modules\Inventory\Models\Product;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->for(Household::factory())->create();
        $this->actingAs($this->user);
    }

    public function test_the_scan_page_is_registered_and_has_a_manual_fallback(): void
    {
        $this->assertContains(Scan::class, app(NavigationRegistry::class)->pages());

        $this->get(Scan::getUrl())
            ->assertOk()
            ->assertSee('Type the barcode')
            ->assertSee('The camera only works when Holdout is opened over HTTPS.');
    }

    public function test_a_known_product_without_stock_goes_straight_to_adding_a_batch(): void
    {
        $product = Product::factory()->for($this->user->household)->create(['barcode' => '4006381333931']);

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->assertRedirect(BatchResource::getUrl('create', ['product' => $product->id]));
    }

    public function test_short_codes_match_the_normalized_barcode(): void
    {
        $product = Product::factory()->for($this->user->household)->create(['barcode' => '0036000291452']);

        Livewire::test(Scan::class)
            ->set('code', '036000291452')
            ->call('scanned')
            ->assertRedirect(BatchResource::getUrl('create', ['product' => $product->id]));
    }

    public function test_an_unknown_barcode_leads_to_adding_the_product(): void
    {
        Product::factory()->create(['barcode' => '4006381333931']); // another household's

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->assertRedirect(ProductResource::getUrl('create', ['barcode' => '4006381333931']));
    }

    public function test_invalid_codes_are_rejected(): void
    {
        Livewire::test(Scan::class)
            ->call('scanned', 'https://example.com')
            ->assertNoRedirect()
            ->assertNotified();
    }

    public function test_adding_a_batch_with_a_product_from_the_scanner_preselects_it(): void
    {
        $mine = Product::factory()->for($this->user->household)->create();
        $other = Product::factory()->create();

        Livewire::withQueryParams(['product' => $mine->id])->test(CreateBatch::class)
            ->assertSchemaStateSet(['product_id' => $mine->id]);

        // Another household's product is ignored.
        Livewire::withQueryParams(['product' => $other->id])->test(CreateBatch::class)
            ->assertSchemaStateSet(['product_id' => null]);
    }

    public function test_adding_a_product_from_the_scanner_fills_the_barcode_and_looks_it_up(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 1, 'product' => ['product_name' => 'Spaghetti', 'brands' => 'Barilla']])]);

        Livewire::withQueryParams(['barcode' => '8076800195057'])->test(CreateProduct::class)
            ->assertSchemaStateSet(['barcode' => '8076800195057', 'name' => 'Spaghetti', 'brand' => 'Barilla']);
    }

    private function productInStock(): Product
    {
        return Product::factory()->for($this->user->household)->create(['barcode' => '4006381333931', 'name' => 'Spaghetti']);
    }

    public function test_a_product_in_stock_shows_its_batches_earliest_first(): void
    {
        $this->user->update(['locale' => 'en']);
        app()->setLocale('en');
        $product = $this->productInStock();
        Batch::factory()->for($product)->create(['quantity' => 4, 'best_before' => '2031-05-20']);
        Batch::factory()->for($product)->create(['quantity' => 2, 'best_before' => '2030-01-15']);
        Batch::factory()->for($product)->create(['quantity' => 7, 'best_before' => null]);

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->assertNoRedirect()
            ->assertSet('productId', $product->id)
            ->assertSeeHtmlInOrder(['Spaghetti', '2 ×', '01/15/2030', '4 ×', '05/20/2031', '7 ×', 'No date'])
            ->assertSee(BatchResource::getUrl('create', ['product' => $product->id]))
            ->call('scanNext')
            ->assertSet('productId', null)
            ->assertDontSee('Spaghetti');
    }

    public function test_packages_can_be_taken_out_after_scanning(): void
    {
        $product = $this->productInStock();
        $batch = Batch::factory()->for($product)->create(['quantity' => 3]);

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->callAction(TestAction::make('takeOut')->arguments(['batch' => $batch->id]), ['quantity' => 2, 'type' => MovementType::Consumed->value])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(1, $batch->fresh()->quantity);
        $movement = Movement::query()->where('type', MovementType::Consumed)->sole();
        $this->assertSame(-2, $movement->quantity);
        $this->assertSame($this->user->id, $movement->user_id);
    }

    public function test_taking_out_the_last_package_after_scanning(): void
    {
        $this->user->update(['locale' => 'en']);
        app()->setLocale('en');
        $batch = Batch::factory()->for($this->productInStock())->create(['quantity' => 1]);

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->callAction(TestAction::make('takeOut')->arguments(['batch' => $batch->id]), ['quantity' => 1, 'type' => MovementType::Consumed->value])
            ->assertNotified()
            ->assertSee('Nothing left in stock.');

        $this->assertModelMissing($batch);
    }

    public function test_more_than_the_stock_cannot_be_taken_out_after_scanning(): void
    {
        $batch = Batch::factory()->for($this->productInStock())->create(['quantity' => 2]);

        Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->callAction(TestAction::make('takeOut')->arguments(['batch' => $batch->id]), ['quantity' => 3, 'type' => MovementType::Consumed->value])
            ->assertHasFormErrors(['quantity']);

        $this->assertSame(2, $batch->fresh()->quantity);
    }

    public function test_batches_of_other_products_or_households_cannot_be_taken_out(): void
    {
        Batch::factory()->for($this->productInStock())->create();
        $foreign = Batch::factory()->create();
        $otherProduct = Batch::factory()->for(Product::factory()->for($this->user->household))->create();

        $page = Livewire::test(Scan::class)->call('scanned', '4006381333931');

        foreach ([$foreign, $otherProduct] as $batch) {
            $page->call('mountAction', 'takeOut', ['batch' => $batch->id])->assertActionNotMounted()->assertNotified('This batch is no longer in stock.');
        }
    }

    public function test_a_batch_emptied_by_someone_else_meanwhile_closes_the_dialog(): void
    {
        $batch = Batch::factory()->for($this->productInStock())->create(['quantity' => 1]);

        $page = Livewire::test(Scan::class)
            ->call('scanned', '4006381333931')
            ->mountAction(TestAction::make('takeOut')->arguments(['batch' => $batch->id]));

        $batch->deleteQuietly();

        $page->call('callMountedAction')
            ->assertActionNotMounted()
            ->assertNotified('This batch is no longer in stock.');
        $this->assertSame(0, Movement::query()->where('type', MovementType::Consumed)->count());
    }

    public function test_a_new_product_can_be_put_into_stock_with_its_best_before_date(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0])]);
        $cellar = Location::factory()->for($this->user->household)->create();

        Livewire::withQueryParams(['barcode' => '8076800195057'])->test(CreateProduct::class)
            ->assertSchemaStateSet(['add_batch' => true])
            ->fillForm([
                'name' => 'Spaghetti',
                'category_id' => Category::query()->where('key', 'grains')->value('id'),
                'package_value' => 500,
                'package_unit' => PackageUnit::Gram,
                'batch.quantity' => 3,
                'batch.best_before' => '2030-01-15',
                'batch.location_id' => $cellar->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $batch = Product::query()->where('barcode', '8076800195057')->sole()->batches()->sole();
        $this->assertSame(3, $batch->quantity);
        $this->assertSame('2030-01-15', $batch->best_before->toDateString());
        $this->assertSame($cellar->id, $batch->location_id);
        $this->assertSame(MovementType::Added, Movement::query()->sole()->type);
    }

    public function test_the_packages_of_a_new_product_are_validated_and_nothing_is_saved_by_half(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0])]);
        $foreign = Location::factory()->create();

        Livewire::withQueryParams(['barcode' => '8076800195057'])->test(CreateProduct::class)
            ->fillForm([
                'name' => 'Spaghetti',
                'category_id' => Category::query()->where('key', 'grains')->value('id'),
                'package_value' => 500,
                'package_unit' => PackageUnit::Gram,
                'batch.quantity' => 0,
                'batch.best_before' => '1999-01-01',
                'batch.location_id' => $foreign->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['batch.quantity', 'batch.best_before', 'batch.location_id']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('batches', 0);
    }

    public function test_a_new_product_without_packages_is_still_possible(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertSchemaStateSet(['add_batch' => false])
            ->fillForm([
                'name' => 'Rice',
                'category_id' => Category::query()->where('key', 'grains')->value('id'),
                'package_value' => 1,
                'package_unit' => PackageUnit::Kilogram,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 0);
    }
}
