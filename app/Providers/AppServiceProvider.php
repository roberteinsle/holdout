<?php

namespace App\Providers;

use App\Filament\Support\ButtonComponent;
use App\Filament\Widgets\SupplyStatusWidget;
use App\Http\Responses\PasskeyLoginResponse;
use App\Printout\SupplySection;
use App\Registries\CategoryRegistry;
use App\Registries\DashboardRegistry;
use App\Registries\NavigationRegistry;
use App\Registries\PrintoutRegistry;
use App\Registries\ProductLookupRegistry;
use App\Registries\StockRegistry;
use Filament\Support\View\Components\ButtonComponent as FilamentButtonComponent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

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
        $this->app->singleton(DashboardRegistry::class);
        $this->app->singleton(PrintoutRegistry::class);

        // Core's own dashboard widget: days of supply and stock against target.
        $this->app->afterResolving(DashboardRegistry::class, function (DashboardRegistry $dashboard): void {
            $dashboard->widget('core', SupplyStatusWidget::class);
        });

        // A passkey sign-in counts as a recent confirmation for changing sign-in methods.
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);

        // Core's section of the printout: days of supply and need per day.
        $this->app->afterResolving(PrintoutRegistry::class, function (PrintoutRegistry $printout): void {
            $printout->section('core', $this->app->make(SupplySection::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Requesting sign-in links / registering: 5 per minute per IP and per address.
        RateLimiter::for('magic-link', fn (Request $request) => [
            Limit::perMinute(5)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('email:'.mb_strtolower((string) $request->input('email'))),
        ]);

        // Code sign-in: a 6-digit code must not be guessable, so limit per address per hour too.
        RateLimiter::for('code-login', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('email:'.mb_strtolower((string) $request->input('email'))),
            Limit::perHour(20)->by('email-hour:'.mb_strtolower((string) $request->input('email'))),
        ]);

        // Redeeming sign-in links and invitations.
        RateLimiter::for('token', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
