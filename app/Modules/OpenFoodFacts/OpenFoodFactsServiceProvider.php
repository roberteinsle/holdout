<?php

namespace App\Modules\OpenFoodFacts;

use App\Registries\ProductLookupRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Open Food Facts module: looks up products by barcode for any feature that
 * asks the ProductLookupRegistry. Data: Open Food Facts, ODbL.
 */
class OpenFoodFactsServiceProvider extends ServiceProvider
{
    public const MODULE = 'openfoodfacts';

    public function register(): void
    {
        $this->app->afterResolving(ProductLookupRegistry::class, function (ProductLookupRegistry $registry): void {
            if (config('services.openfoodfacts.enabled')) {
                $registry->register(self::MODULE, $this->app->make(OpenFoodFactsLookup::class));
            }
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
