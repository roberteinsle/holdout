<?php

namespace App\Providers;

use App\Filament\Support\ButtonComponent;
use App\Registries\CategoryRegistry;
use App\Registries\NavigationRegistry;
use App\Registries\ProductLookupRegistry;
use App\Registries\StockRegistry;
use Filament\Support\View\Components\ButtonComponent as FilamentButtonComponent;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Filled buttons follow the Holdout colour roles (see App\Support\Palette).
        $this->app->bind(FilamentButtonComponent::class, ButtonComponent::class);

        // Registries: modules register into them, core features only read from them.
        $this->app->singleton(CategoryRegistry::class);
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(ProductLookupRegistry::class);
        $this->app->singleton(StockRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
