<?php

namespace App\Providers;

use App\Filament\Support\ButtonComponent;
use Filament\Support\View\Components\ButtonComponent as FilamentButtonComponent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
