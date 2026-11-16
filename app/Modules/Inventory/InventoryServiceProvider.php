<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Filament\Pages\Scan;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Policies\BatchPolicy;
use App\Modules\Inventory\Policies\ProductPolicy;
use App\Registries\NavigationRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Inventory module: the household's products and their batches (amount and
 * best-before date; locations and movements follow). Categories come from the core Category model,
 * which the CategoryRegistry fills.
 */
class InventoryServiceProvider extends ServiceProvider
{
    public const MODULE = 'inventory';

    public function register(): void
    {
        $this->app->afterResolving(NavigationRegistry::class, function (NavigationRegistry $navigation): void {
            $navigation->resource(self::MODULE, ProductResource::class);
            $navigation->page(self::MODULE, Scan::class);
            $navigation->resource(self::MODULE, BatchResource::class);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'inventory');

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Batch::class, BatchPolicy::class);
    }
}
