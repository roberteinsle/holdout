<?php

namespace App\Modules\Equipment;

use App\Modules\Equipment\Filament\Resources\Equipment\EquipmentResource;
use App\Modules\Equipment\Models\EquipmentItem;
use App\Modules\Equipment\Policies\EquipmentItemPolicy;
use App\Modules\Equipment\Printout\EquipmentSection;
use App\Registries\NavigationRegistry;
use App\Registries\PrintoutRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Equipment module: everything beyond food (home pharmacy, power outage,
 * hygiene, documents, cash, grab bag, fire safety, staying informed, other
 * equipment), with expiry dates, regular checks and a check log. Connected
 * through the NavigationRegistry (page) and the PrintoutRegistry (checklist).
 */
class EquipmentServiceProvider extends ServiceProvider
{
    public const MODULE = 'equipment';

    public function register(): void
    {
        $this->app->afterResolving(NavigationRegistry::class, function (NavigationRegistry $navigation): void {
            $navigation->resource(self::MODULE, EquipmentResource::class);
        });

        $this->app->afterResolving(PrintoutRegistry::class, function (PrintoutRegistry $printout): void {
            $printout->section(self::MODULE, new EquipmentSection);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'equipment');

        Gate::policy(EquipmentItem::class, EquipmentItemPolicy::class);
    }
}
