<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Enums\PackageUnit;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Product;
use App\Registries\NavigationRegistry;
use Database\Seeders\CategorySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTest extends TestCase
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

    private function category(string $key): Category
    {
        return Category::query()->where('key', $key)->sole();
    }

    public function test_the_inventory_module_registers_its_page_through_the_navigation_registry(): void
    {
        $this->assertContains(ProductResource::class, app(NavigationRegistry::class)->resources());
        $this->get(ProductResource::getUrl('index'))->assertOk()->assertSee('No products yet');
    }

    public function test_a_product_is_created_for_the_current_household(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Spaghetti',
                'brand' => 'Barilla',
                'category_id' => $this->category('grains')->id,
                'package_value' => 500,
                'package_unit' => PackageUnit::Gram->value,
                'barcode' => '8076800195057',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->sole();
        $this->assertSame($this->user->household_id, $product->household_id);
        $this->assertSame('Spaghetti', $product->name);
        $this->assertSame(PackageUnit::Gram, $product->package_unit);
        $this->assertSame(0.5, $product->packageInBaseUnit());
        $this->assertSame('500 g', $product->packageLabel());
    }

    public function test_input_is_validated(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => '',
                'category_id' => 99999,
                'package_value' => 0,
                'package_unit' => 'pieces',
                'barcode' => '12ab',
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'category_id', 'package_value', 'package_unit', 'barcode']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_products_can_be_filed_under_other(): void
    {
        $this->get(ProductResource::getUrl('create'))->assertSee('Other');

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Salt', 'category_id' => $this->category('other')->id, 'package_value' => 500, 'package_unit' => 'g'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('other', Product::query()->sole()->category->key);
    }

    public function test_a_barcode_is_unique_within_the_household_only(): void
    {
        Product::factory()->for($this->user->household)->create(['barcode' => '4001234567890']);
        Product::factory()->create(['barcode' => '4009876543210']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Twice', 'category_id' => $this->category('grains')->id, 'package_value' => 1, 'package_unit' => 'kg', 'barcode' => '4001234567890'])
            ->call('create')
            ->assertHasFormErrors(['barcode' => 'unique']);

        // The same barcode in another household is fine.
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Elsewhere', 'category_id' => $this->category('grains')->id, 'package_value' => 1, 'package_unit' => 'kg', 'barcode' => '4009876543210'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_products_are_listed_edited_and_deleted_within_the_household_only(): void
    {
        $mine = Product::factory()->for($this->user->household)->create(['name' => 'Rice', 'category_id' => $this->category('grains')->id]);
        $other = Product::factory()->create(['name' => 'Neighbour rice']);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertSee('Grains, bread, potatoes');

        Livewire::test(EditProduct::class, ['record' => $mine->getRouteKey()])
            ->fillForm(['name' => 'Basmati rice', 'package_value' => 1, 'package_unit' => 'kg'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Basmati rice', $mine->fresh()->name);
        $this->assertSame(1.0, $mine->fresh()->packageInBaseUnit());

        $this->get(ProductResource::getUrl('edit', ['record' => $other]))->assertNotFound();

        Livewire::test(EditProduct::class, ['record' => $mine->getRouteKey()])->callAction('delete');
        $this->assertModelMissing($mine);
    }

    public function test_leaving_the_barcode_field_fills_the_form_from_the_product_database(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 1, 'product' => [
            'product_name' => 'Spaghetti n.5', 'brands' => 'Barilla', 'product_quantity' => 500, 'product_quantity_unit' => 'g',
            'categories_tags' => ['en:pastas'],
        ]])]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['barcode' => '8076800195057'])
            ->assertSchemaStateSet([
                'name' => 'Spaghetti n.5',
                'brand' => 'Barilla',
                'package_value' => 500.0,
                'package_unit' => PackageUnit::Gram,
                'category_id' => $this->category('grains')->id,
            ])
            ->assertNotified()
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('8076800195057', Product::query()->sole()->barcode);
    }

    public function test_the_lookup_does_not_overwrite_what_was_typed_unless_asked(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 1, 'product' => ['product_name' => 'From database', 'brands' => 'Brand']])]);

        $page = Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'My name', 'barcode' => '8076800195057'])
            ->assertSchemaStateSet(['name' => 'My name']);

        $page->callAction(TestAction::make('lookUp')->schemaComponent('barcode'))
            ->assertSchemaStateSet(['name' => 'From database', 'brand' => 'Brand']);
    }

    public function test_an_upc_barcode_is_stored_with_13_digits(): void
    {
        Http::fake(['*' => Http::response(['status' => 0], 404)]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Soup', 'category_id' => $this->category('vegetables')->id, 'package_value' => 400, 'package_unit' => 'g', 'barcode' => '036000291452'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('0036000291452', Product::query()->sole()->barcode);
    }

    public function test_when_the_product_database_is_offline_the_form_still_works(): void
    {
        Http::fake(fn () => throw new ConnectionException('offline'));

        Livewire::test(CreateProduct::class)
            ->fillForm(['barcode' => '8076800195057'])
            ->assertNotified(__('The product database cannot be reached right now.'))
            ->fillForm(['name' => 'Spaghetti', 'category_id' => $this->category('grains')->id, 'package_value' => 500, 'package_unit' => 'g'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_litres_count_like_kilograms_for_the_supply(): void
    {
        $milk = Product::factory()->for($this->user->household)->create([
            'category_id' => $this->category('dairy')->id,
            'package_value' => 1.5,
            'package_unit' => PackageUnit::Litre,
        ]);

        $this->assertSame(1.5, $milk->packageInBaseUnit());
        $this->assertSame(0.25, Product::factory()->make(['package_value' => 250, 'package_unit' => PackageUnit::Millilitre])->packageInBaseUnit());
    }
}
