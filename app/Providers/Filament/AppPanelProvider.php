<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Profile;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\SetLocale;
use App\Registries\DashboardRegistry;
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
use Filament\View\PanelsRenderHook;
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
            ->brandLogo(fn (): string => asset('logo.svg'))
            ->darkModeBrandLogo(fn (): string => asset('logo-dark.svg'))
            ->brandLogoHeight('2rem')
            // Favicons, the iOS home-screen icon, theme colour and the manifest.
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('partials.head-icons')->render())
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
            // Dashboard widgets come from the DashboardRegistry (core and modules).
            ->widgets(app(DashboardRegistry::class)->widgets())
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
                'profile' => Action::make('profile')
                    ->label(fn (): string => __('Profile'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (): string => Profile::getUrl()),
                'logout' => fn (Action $action): Action => $action
                    ->label(fn (): string => __('Switch person'))
                    ->icon(Heroicon::OutlinedArrowsRightLeft),
            ]);
    }
}
