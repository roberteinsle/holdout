<?php

namespace App\Modules\OpenFoodFacts;

use App\Registries\CategoryRegistry;
use App\Registries\ProductInfo;

/**
 * Turns an Open Food Facts product into a ProductInfo: name in the user's
 * language, first brand, package size and a category hint.
 */
class ProductMapper
{
    /**
     * Open Food Facts category tags => CategoryRegistry key. Checked in this
     * order, so e.g. plant milk (dairy substitute and beverage) counts as dairy.
     *
     * @var array<string, list<string>>
     */
    private const CATEGORY_TAGS = [
        'protein' => ['en:meats', 'en:fishes', 'en:seafood', 'en:eggs', 'en:sausages', 'en:canned-fishes'],
        'dairy' => ['en:dairies', 'en:milks', 'en:cheeses', 'en:yogurts', 'en:dairy-substitutes'],
        'fats' => ['en:fats', 'en:vegetable-oils', 'en:oils', 'en:butters'],
        'fruit' => ['en:fruits', 'en:nuts', 'en:dried-fruits', 'en:fruit-based-foods'],
        'vegetables' => ['en:vegetables', 'en:legumes', 'en:pulses', 'en:mushrooms', 'en:canned-vegetables', 'en:vegetables-based-foods'],
        'grains' => ['en:cereals-and-potatoes', 'en:cereals-and-their-products', 'en:pastas', 'en:rices', 'en:breads', 'en:breakfast-cereals', 'en:potatoes', 'en:flours'],
        'water' => ['en:waters', 'en:mineral-waters', 'en:spring-waters', 'en:beverages'],
    ];

    public function __construct(private CategoryRegistry $categories) {}

    /** @param array<string, mixed> $product */
    public function map(string $barcode, array $product, string $locale): ProductInfo
    {
        [$value, $unit] = $this->package($product);

        return new ProductInfo(
            barcode: $barcode,
            source: 'Open Food Facts',
            name: $this->name($product, $locale),
            brand: $this->brand($product),
            packageValue: $value,
            packageUnit: $unit,
            categoryKey: $this->categoryKey($product),
        );
    }

    /** @param array<string, mixed> $product */
    private function name(array $product, string $locale): ?string
    {
        foreach (["product_name_{$locale}", 'product_name', 'product_name_en', 'generic_name'] as $field) {
            $name = trim((string) ($product[$field] ?? ''));

            if ($name !== '') {
                return mb_substr($name, 0, 150);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $product */
    private function brand(array $product): ?string
    {
        $brand = trim(explode(',', (string) ($product['brands'] ?? ''))[0]);

        return $brand === '' ? null : mb_substr($brand, 0, 100);
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array{0: ?float, 1: ?string}
     */
    private function package(array $product): array
    {
        $quantity = $product['product_quantity'] ?? null;
        $unit = strtolower((string) ($product['product_quantity_unit'] ?? ''));

        if (is_numeric($quantity) && (float) $quantity > 0 && in_array($unit, ['g', 'ml'], true)) {
            return $this->tidy((float) $quantity, $unit);
        }

        // Fall back to the free text, e.g. "500 g", "1,5 l", "6 x 33 cl".
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(kg|g|ml|cl|l)\b/i', (string) ($product['quantity'] ?? ''), $matches, PREG_SET_ORDER)) {
            $match = end($matches);
            $value = (float) str_replace(',', '.', $match[1]);
            $unit = strtolower($match[2]);

            if ($unit === 'cl') {
                [$value, $unit] = [$value * 10, 'ml'];
            }

            if (preg_match('/(\d+)\s*[x×]\s*/i', (string) $product['quantity'], $multi)) {
                $value *= (int) $multi[1];
            }

            return $value > 0 ? $this->tidy($value, $unit) : [null, null];
        }

        return [null, null];
    }

    /** 1500 g => 1.5 kg, 750 ml stays. @return array{0: float, 1: string} */
    private function tidy(float $value, string $unit): array
    {
        if ($unit === 'g' && $value >= 1000) {
            return [round($value / 1000, 3), 'kg'];
        }

        if ($unit === 'ml' && $value >= 1000) {
            return [round($value / 1000, 3), 'l'];
        }

        return [round($value, 3), $unit];
    }

    /** @param array<string, mixed> $product */
    private function categoryKey(array $product): ?string
    {
        $tags = (array) ($product['categories_tags'] ?? []);

        foreach (self::CATEGORY_TAGS as $key => $candidates) {
            if (array_intersect($candidates, $tags) !== [] && $this->categories->get($key) !== null) {
                return $key;
            }
        }

        return null;
    }
}
