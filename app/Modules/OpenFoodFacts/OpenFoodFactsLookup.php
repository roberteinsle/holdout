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
use Illuminate\Support\Facades\Storage;

/**
 * Product data from Open Food Facts (https://world.openfoodfacts.org), with a
 * local cache: fresh answers are reused, and when the internet is down any
 * stored answer is used. Requests are rate limited for the whole installation.
 */
class OpenFoodFactsLookup implements ProductLookup
{
    private const FIELDS = 'code,product_name,product_name_en,product_name_de,product_name_es,product_name_pt,product_name_fr,generic_name,brands,quantity,product_quantity,product_quantity_unit,categories_tags,image_front_url,image_url';

    private const MAX_IMAGE_BYTES = 2_000_000;

    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

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
            'image_path' => $product !== null ? ($this->downloadImage($barcode, $product) ?? $cached?->image_path) : null,
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

    /**
     * Download the front photo once to the data disk, so it also shows offline.
     * Only https URLs on openfoodfacts.org, only real JPEG/PNG/WebP images up to
     * 2 MB. A failed download never fails the lookup.
     *
     * @param  array<string, mixed>  $product
     */
    private function downloadImage(string $barcode, array $product): ?string
    {
        $url = $product['image_front_url'] ?? $product['image_url'] ?? null;
        $host = is_string($url) ? (string) parse_url($url, PHP_URL_HOST) : '';

        if (! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ($host !== 'openfoodfacts.org' && ! str_ends_with($host, '.openfoodfacts.org'))) {
            return null;
        }

        if (! RateLimiter::attempt('openfoodfacts', (int) config('services.openfoodfacts.requests_per_minute'), fn (): bool => true)) {
            return null;
        }

        try {
            $response = Http::withUserAgent((string) config('services.openfoodfacts.user_agent'))
                ->timeout((int) config('services.openfoodfacts.image_timeout'))
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }

        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $body = $response->body();

        if (! $response->successful() || ! isset(self::IMAGE_TYPES[$type])
            || strlen($body) > self::MAX_IMAGE_BYTES || @getimagesizefromstring($body) === false) {
            return null;
        }

        $path = 'off-images/'.$barcode.'.'.self::IMAGE_TYPES[$type];

        return Storage::disk('data')->put($path, $body) ? $path : null;
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

        $imagePath = $cached->image_path !== null && Storage::disk('data')->exists($cached->image_path) ? $cached->image_path : null;

        return $this->mapper->map(Barcode::normalize($cached->barcode) ?? $cached->barcode, $cached->payload, $locale, $imagePath);
    }
}
