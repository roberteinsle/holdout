<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Modules\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Inventory\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Inventory\Models\Product;
use App\Modules\OpenFoodFacts\Models\CachedProduct;
use App\Registries\ProductLookupRegistry;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('data');
        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->for(Household::factory())->create();
        $this->actingAs($this->user);
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $product */
    private function fakeOpenFoodFacts(array $product, ?string $imageBody = null, string $imageType = 'image/png'): void
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(['status' => 1, 'product' => $product]),
            'images.openfoodfacts.org/*' => Http::response($imageBody ?? $this->png(), 200, ['Content-Type' => $imageType]),
            '*' => Http::response('', 500),
        ]);
    }

    public function test_the_photo_is_downloaded_once_and_stored_with_the_cache(): void
    {
        $this->fakeOpenFoodFacts(['product_name' => 'Spaghetti', 'image_front_url' => 'https://images.openfoodfacts.org/images/products/807/680/019/5057/front_en.400.jpg']);

        $info = app(ProductLookupRegistry::class)->lookup('8076800195057', 'en');

        $this->assertSame('off-images/8076800195057.png', $info->imagePath);
        $this->assertSame('Open Food Facts (CC BY-SA)', $info->imageCredit);
        Storage::disk('data')->assertExists('off-images/8076800195057.png');

        // Offline later: the stored photo still comes along.
        Http::fake(fn () => throw new ConnectionException('offline'));
        CachedProduct::query()->find('8076800195057')->update(['fetched_at' => now()->subYear()]);
        $this->assertSame('off-images/8076800195057.png', app(ProductLookupRegistry::class)->lookup('8076800195057', 'en')->imagePath);
    }

    public function test_photos_are_only_downloaded_from_open_food_facts(): void
    {
        $this->fakeOpenFoodFacts(['product_name' => 'Spaghetti', 'image_front_url' => 'https://evil.example.com/x.png']);

        $this->assertNull(app(ProductLookupRegistry::class)->lookup('8076800195057', 'en')->imagePath);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'evil.example.com'));
    }

    public function test_files_that_are_not_images_are_rejected(): void
    {
        $this->fakeOpenFoodFacts(['product_name' => 'Spaghetti', 'image_front_url' => 'https://images.openfoodfacts.org/x.jpg'], '<script>alert(1)</script>', 'image/jpeg');

        $this->assertNull(app(ProductLookupRegistry::class)->lookup('8076800195057', 'en')->imagePath);
        Storage::disk('data')->assertMissing('off-images/8076800195057.jpg');
    }

    public function test_a_found_product_brings_its_photo_into_the_form(): void
    {
        $this->fakeOpenFoodFacts(['product_name' => 'Spaghetti', 'product_quantity' => 500, 'product_quantity_unit' => 'g', 'categories_tags' => ['en:pastas'], 'image_front_url' => 'https://images.openfoodfacts.org/front.png']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['barcode' => '8076800195057'])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->sole();
        $this->assertStringStartsWith('product-images/', $product->image_path);
        $this->assertSame('Open Food Facts (CC BY-SA)', $product->image_credit);
        Storage::disk('data')->assertExists($product->image_path);
        // The product has its own copy, independent of the lookup cache.
        Storage::disk('data')->assertExists('off-images/8076800195057.png');
    }

    public function test_a_photo_can_be_taken_or_chosen_by_hand(): void
    {
        Http::fake(['*' => Http::response(['status' => 0], 404)]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Home-made jam',
                'category_id' => Category::query()->where('key', 'fruit')->value('id'),
                'package_value' => 400,
                'package_unit' => 'g',
                'image_path' => UploadedFile::fake()->image('jam.jpg', 1200, 900),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->sole();
        $this->assertStringStartsWith('product-images/', $product->image_path);
        $this->assertNull($product->image_credit);
        Storage::disk('data')->assertExists($product->image_path);
    }

    public function test_only_images_up_to_5_mb_are_accepted(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['image_path' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->call('create')
            ->assertHasFormErrors(['image_path']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['image_path' => UploadedFile::fake()->image('big.jpg')->size(6000)])
            ->call('create')
            ->assertHasFormErrors(['image_path']);
    }

    public function test_deleting_a_product_deletes_its_photo(): void
    {
        Storage::disk('data')->put('product-images/a.png', $this->png());
        $product = Product::factory()->for($this->user->household)->create(['image_path' => 'product-images/a.png']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->callAction('delete');

        Storage::disk('data')->assertMissing('product-images/a.png');
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::disk('data')->put('product-images/old.png', $this->png());
        $product = Product::factory()->for($this->user->household)->create(['image_path' => 'product-images/old.png']);

        $product->update(['image_path' => 'product-images/new.png']);

        Storage::disk('data')->assertMissing('product-images/old.png');
    }

    public function test_the_list_shows_the_photo_through_a_signed_temporary_url(): void
    {
        // The real disk configuration (signed URLs at /files), in a temporary folder.
        config(['filesystems.disks.data.root' => storage_path('framework/testing/data-'.uniqid())]);
        Storage::forgetDisk('data');
        Storage::disk('data')->put('product-images/a.png', $this->png());
        Product::factory()->for($this->user->household)->create(['name' => 'Rice', 'image_path' => 'product-images/a.png']);

        $html = Livewire::test(ListProducts::class)->html();
        preg_match('#src="([^"]*/files/product-images/a\.png[^"]*)"#', $html, $match);
        $this->assertNotEmpty($match, 'No signed photo URL in the list.');

        $url = html_entity_decode($match[1]);
        $this->get($url)->assertOk();
        // Without the signature the file is not served.
        $this->get(strtok($url, '?'))->assertForbidden();

        Storage::disk('data')->deleteDirectory('');
    }
}
