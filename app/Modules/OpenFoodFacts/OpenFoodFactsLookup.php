<?php

namespace App\Modules\OpenFoodFacts;

use App\Modules\OpenFoodFacts\Models\CachedProduct;
use App\Registries\ProductInfo;
use App\Registries\ProductLookup;
use App\Registries\ProductLookupUnavailable;
use App\Support\Barcode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Product data from Open Food Facts (https://world.openfoodfacts.org), with a
 * local cache: fresh answers are reused, and when the internet is down any
 * stored answer is used. Requests are rate limited for the whole installation.
 */
class OpenFoodFactsLookup implements ProductLookup
{
    private const FIELDS = 'code,product_name,product_name_en,product_name_de,product_name_es,product_name_pt,product_name_fr,generic_name,brands,quantity,product_quantity,product_quantity_unit,categories_tags';

    public function __construct(private ProductMapper $mapper) {}

    public function lookup(string $barcode, string $locale): ?ProductInfo
    {
        $cached = CachedProduct::query()->find($barcode);

        if ($cached !== null && $this->isFresh($cached)) {
            return $this->fromCache($cached, $locale);
        }

        try {
            $product = $this->fetch($barcode);
        } catch (ProductLookupUnavailable $exception) {
            if ($cached !== null) {
                return $this->fromCache($cached, $locale);
            }

            throw $exception;
        }

        $cached = CachedProduct::query()->updateOrCreate(['barcode' => $barcode], [
            'found' => $product !== null,
            'payload' => $product,
            'fetched_at' => now(),
        ]);

        return $this->fromCache($cached, $locale);
    }

    /** @return array<string, mixed>|null the product, or null when Open Food Facts does not know it */
    private function fetch(string $barcode): ?array
    {
        $allowed = RateLimiter::attempt(
            'openfoodfacts',
            (int) config('services.openfoodfacts.requests_per_minute'),
            fn (): bool => true,
        );

        if (! $allowed) {
            throw new ProductLookupUnavailable('Open Food Facts rate limit reached.');
        }

        // EAN-8 codes are padded to 13 digits here; Open Food Facts may know them in the short form.
        $codes = str_starts_with($barcode, '00000') ? [$barcode, substr($barcode, -8)] : [$barcode];

        foreach ($codes as $code) {
            if (($product = $this->request($code)) !== null) {
                return $product;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function request(string $code): ?array
    {
        try {
            $response = Http::baseUrl((string) config('services.openfoodfacts.base_url'))
                ->withUserAgent((string) config('services.openfoodfacts.user_agent'))
                ->acceptJson()
                ->timeout((int) config('services.openfoodfacts.timeout'))
                ->get("/api/v2/product/{$code}", ['fields' => self::FIELDS]);
        } catch (ConnectionException $exception) {
            throw new ProductLookupUnavailable('Open Food Facts is not reachable.', previous: $exception);
        }

        if ($response->status() === 404 || ($response->successful() && (int) $response->json('status') === 0)) {
            return null;
        }

        if (! $response->successful() || ! is_array($response->json('product'))) {
            throw new ProductLookupUnavailable("Open Food Facts answered with HTTP {$response->status()}.");
        }

        return $response->json('product');
    }

    private function isFresh(CachedProduct $cached): bool
    {
        $days = $cached->found
            ? (int) config('services.openfoodfacts.cache_days')
            : (int) config('services.openfoodfacts.not_found_cache_days');

        return $cached->fetched_at->gt(now()->subDays($days));
    }

    private function fromCache(CachedProduct $cached, string $locale): ?ProductInfo
    {
        if (! $cached->found || ! is_array($cached->payload)) {
            return null;
        }

        return $this->mapper->map(Barcode::normalize($cached->barcode) ?? $cached->barcode, $cached->payload, $locale);
    }
}
