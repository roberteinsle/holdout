<?php

namespace App\Registries;

use App\Support\Barcode;

/**
 * Modules register product data sources here; features such as the product form
 * ask the registry and never a source directly. Sources are asked in order.
 */
class ProductLookupRegistry
{
    /** @var array<string, ProductLookup> module => source */
    private array $sources = [];

    public function register(string $module, ProductLookup $source): void
    {
        $this->sources[$module] = $source;
    }

    public function hasSources(): bool
    {
        return $this->sources !== [];
    }

    /**
     * @throws ProductLookupUnavailable when no source found it and at least one could not be asked
     */
    public function lookup(string $barcode, string $locale): ?ProductInfo
    {
        $normalized = Barcode::normalize($barcode);

        if ($normalized === null) {
            return null;
        }

        $unavailable = null;

        foreach ($this->sources as $source) {
            try {
                if ($info = $source->lookup($normalized, $locale)) {
                    return $info;
                }
            } catch (ProductLookupUnavailable $exception) {
                $unavailable = $exception;
            }
        }

        if ($unavailable !== null) {
            throw $unavailable;
        }

        return null;
    }
}
