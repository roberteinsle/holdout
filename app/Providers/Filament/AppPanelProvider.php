<?php

namespace App\Providers\Filament;

use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\SetLocale;
use App\Registries\NavigationRegistry;
use App\Support\Palette;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('')
            ->brandName('Holdout')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->colors(Palette::filament())
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            // Modules register their resources and pages in the NavigationRegistry.
            ->resources(app(NavigationRegistry::class)->resources())
            ->pages(app(NavigationRegistry::class)->pages())
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SetLocale::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // No sign-in on the home network: "log out" means "switch person".
            ->userMenuItems([
                'logout' => fn (Action $action): Action => $action
                    ->label(__('Switch person'))
                    ->icon(Heroicon::OutlinedArrowsRightLeft),
            ]);
    }
}
