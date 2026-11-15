<?php

namespace Tests\Feature;

use App\Modules\OpenFoodFacts\Models\CachedProduct;
use App\Registries\ProductLookupRegistry;
use App\Registries\ProductLookupUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OpenFoodFactsTest extends TestCase
{
    use RefreshDatabase;

    private const SPAGHETTI = [
        'status' => 1,
        'product' => [
            'code' => '8076800195057',
            'product_name' => 'Spaghetti n.5',
            'product_name_de' => 'Spaghetti Nr. 5',
            'brands' => 'Barilla, Barilla G. e R. Fratelli',
            'quantity' => '500 g',
            'product_quantity' => '500',
            'product_quantity_unit' => 'g',
            'categories_tags' => ['en:plant-based-foods', 'en:cereals-and-potatoes', 'en:pastas'],
        ],
    ];

    private function lookup(string $barcode, string $locale = 'en')
    {
        return app(ProductLookupRegistry::class)->lookup($barcode, $locale);
    }

    public function test_a_known_barcode_returns_name_brand_size_and_category(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(self::SPAGHETTI)]);

        $info = $this->lookup('8076800195057', 'de');

        $this->assertSame('Spaghetti Nr. 5', $info->name);
        $this->assertSame('Barilla', $info->brand);
        $this->assertSame(500.0, $info->packageValue);
        $this->assertSame('g', $info->packageUnit);
        $this->assertSame('grains', $info->categoryKey);
        $this->assertSame('Open Food Facts', $info->source);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/api/v2/product/8076800195057')
                && str_starts_with($request->header('User-Agent')[0], 'Holdout/')
                && ! str_contains($request->header('User-Agent')[0], '@');
        });
    }

    public function test_answers_are_cached_and_reused_without_asking_again(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(self::SPAGHETTI)]);

        $this->lookup('8076800195057');
        $this->lookup('8076800195057');

        Http::assertSentCount(1);
        $this->assertTrue(CachedProduct::query()->find('8076800195057')->found);
    }

    public function test_without_internet_a_stored_answer_is_used_even_when_old(): void
    {
        CachedProduct::query()->create(['barcode' => '8076800195057', 'found' => true, 'payload' => self::SPAGHETTI['product'], 'fetched_at' => now()->subYear()]);
        Http::fake(fn () => throw new ConnectionException('offline'));

        $this->assertSame('Spaghetti n.5', $this->lookup('8076800195057')->name);
    }

    public function test_without_internet_and_without_a_stored_answer_the_lookup_is_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('offline'));

        $this->expectException(ProductLookupUnavailable::class);
        $this->lookup('4006381333931');
    }

    public function test_unknown_barcodes_are_remembered_for_a_while(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0, 'status_verbose' => 'product not found'], 404)]);

        $this->assertNull($this->lookup('4006381333931'));
        $this->assertNull($this->lookup('4006381333931'));
        Http::assertSentCount(1);

        $this->travel(8)->days();
        $this->assertNull($this->lookup('4006381333931'));
        Http::assertSentCount(2);
    }

    public function test_requests_are_rate_limited(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 404)]);
        config(['services.openfoodfacts.requests_per_minute' => 2]);
        RateLimiter::clear('openfoodfacts');

        $this->lookup('4006381333931');
        $this->lookup('4006381333948');

        $this->expectException(ProductLookupUnavailable::class);
        $this->lookup('4006381333955');
    }

    public function test_short_ean8_codes_are_also_asked_in_their_short_form(): void
    {
        Http::fake([
            'world.openfoodfacts.org/api/v2/product/0000096385074*' => Http::response(['status' => 0], 404),
            'world.openfoodfacts.org/api/v2/product/96385074*' => Http::response(['status' => 1, 'product' => ['product_name' => 'Kaugummi', 'quantity' => '6 x 33 cl']]),
        ]);

        $info = $this->lookup('96385074');

        $this->assertSame('Kaugummi', $info->name);
        $this->assertSame(1.98, $info->packageValue);
        $this->assertSame('l', $info->packageUnit);
        $this->assertSame('0000096385074', $info->barcode);
    }

    public function test_drinks_and_dairy_are_recognised(): void
    {
        Http::fake([
            '*/4000000000001*' => Http::response(['status' => 1, 'product' => ['product_name' => 'Water', 'product_quantity' => 1500, 'product_quantity_unit' => 'ml', 'categories_tags' => ['en:beverages', 'en:waters']]]),
            '*/4000000000002*' => Http::response(['status' => 1, 'product' => ['product_name' => 'Oat drink', 'quantity' => '1 l', 'categories_tags' => ['en:beverages', 'en:dairy-substitutes']]]),
        ]);

        $water = $this->lookup('4000000000001');
        $this->assertSame(['water', 1.5, 'l'], [$water->categoryKey, $water->packageValue, $water->packageUnit]);
        $this->assertSame('dairy', $this->lookup('4000000000002')->categoryKey);
    }

    public function test_the_lookup_can_be_switched_off(): void
    {
        config(['services.openfoodfacts.enabled' => false]);
        $this->app->forgetInstance(ProductLookupRegistry::class);
        $this->app->singleton(ProductLookupRegistry::class);

        $this->assertFalse(app(ProductLookupRegistry::class)->hasSources());
    }
}
