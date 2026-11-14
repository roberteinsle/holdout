<?php

namespace App\Providers;

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
        //
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

        // Redeeming sign-in links and invitations.
        RateLimiter::for('token', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
