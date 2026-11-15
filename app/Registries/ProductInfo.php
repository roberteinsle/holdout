<?php

namespace App\Registries;

/**
 * What a product lookup found for a barcode. Package unit is g, kg, ml or l;
 * categoryKey is a key from the CategoryRegistry, if the source could tell.
 */
final readonly class ProductInfo
{
    public function __construct(
        public string $barcode,
        public string $source,
        public ?string $name = null,
        public ?string $brand = null,
        public ?float $packageValue = null,
        public ?string $packageUnit = null,
        public ?string $categoryKey = null,
    ) {}
}
