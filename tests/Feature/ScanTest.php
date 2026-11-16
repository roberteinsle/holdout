<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Filament\Pages\Scan;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Batches\Pages\CreateBatch;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Product;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
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

    public function test_a_known_product_goes_straight_to_adding_a_batch(): void
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
}
