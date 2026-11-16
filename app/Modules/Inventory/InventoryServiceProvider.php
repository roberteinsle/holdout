<?php

namespace App\Modules\Inventory;

use App\Modules\Inventory\Filament\Pages\Scan;
use App\Modules\Inventory\Filament\Resources\Batches\BatchResource;
use App\Modules\Inventory\Filament\Resources\Locations\LocationResource;
use App\Modules\Inventory\Filament\Resources\Movements\MovementResource;
use App\Modules\Inventory\Filament\Resources\Products\ProductResource;
use App\Modules\Inventory\Filament\Widgets\EatSoonWidget;
use App\Modules\Inventory\Filament\Widgets\QuickActionsWidget;
use App\Modules\Inventory\Filament\Widgets\RecentActivityWidget;
use App\Modules\Inventory\Models\Batch;
use App\Modules\Inventory\Models\Location;
use App\Modules\Inventory\Models\Movement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Policies\BatchPolicy;
use App\Modules\Inventory\Policies\LocationPolicy;
use App\Modules\Inventory\Policies\MovementPolicy;
use App\Modules\Inventory\Policies\ProductPolicy;
use App\Modules\Inventory\Printout\EatFirstSection;
use App\Modules\Inventory\Printout\StockSection;
use App\Modules\Inventory\Support\InventoryStock;
use App\Registries\DashboardRegistry;
use App\Registries\NavigationRegistry;
use App\Registries\PrintoutRegistry;
use App\Registries\StockRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Inventory module: the household's products, their batches (amount and
 * best-before date), storage locations and the stock history (movements). Categories come from the core Category model,
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
            $navigation->resource(self::MODULE, LocationResource::class);
            $navigation->resource(self::MODULE, MovementResource::class);
        });

        $this->app->afterResolving(DashboardRegistry::class, function (DashboardRegistry $dashboard): void {
            $dashboard->widget(self::MODULE, QuickActionsWidget::class);
            $dashboard->widget(self::MODULE, EatSoonWidget::class);
            $dashboard->widget(self::MODULE, RecentActivityWidget::class);
        });

        // The stock per category, for the supply status (stock against target).
        $this->app->afterResolving(StockRegistry::class, function (StockRegistry $stock): void {
            $stock->register(self::MODULE, $this->app->make(InventoryStock::class));
        });

        // The printout (paper emergency copy): what to eat first, and everything by location.
        $this->app->afterResolving(PrintoutRegistry::class, function (PrintoutRegistry $printout): void {
            $printout->section(self::MODULE, new EatFirstSection);
            $printout->section(self::MODULE, new StockSection);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'inventory');

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Batch::class, BatchPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Movement::class, MovementPolicy::class);
    }
}
