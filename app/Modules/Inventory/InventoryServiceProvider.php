<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Policies\ProductPolicy;
use App\Registries\NavigationRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Inventory module: the household's products (later batches with best-before
 * dates, locations and movements). Categories come from the core Category model,
 * which the CategoryRegistry fills.
 */
class InventoryServiceProvider extends ServiceProvider
{
    public const MODULE = 'inventory';

    public function register(): void
    {
        $this->app->afterResolving(NavigationRegistry::class, function (NavigationRegistry $navigation): void {
            $navigation->resource(self::MODULE, ProductResource::class);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Gate::policy(Product::class, ProductPolicy::class);
    }
}
