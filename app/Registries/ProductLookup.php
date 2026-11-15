<?php

namespace App\Registries;

/**
 * A source of product data by barcode (e.g. Open Food Facts), registered by a
 * module in the ProductLookupRegistry.
 */
interface ProductLookup
{
    /**
     * @param  string  $barcode  normalized, 13 digits
     *
     * @throws ProductLookupUnavailable when the source cannot be asked right now (offline, rate limit)
     */
    public function lookup(string $barcode, string $locale): ?ProductInfo;
}
