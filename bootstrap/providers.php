<?php

use App\Modules\Supply\SupplyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AppPanelProvider;

return [
    AppServiceProvider::class,
    // Modules: each registers its parts in the registries (app/Registries).
    SupplyServiceProvider::class,
    AppPanelProvider::class,
];
