<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\RateLimiter::for('ai-generate', function (\Illuminate\Http\Request $request) {
            return $request->user()?->isAdmin()
                ? \Illuminate\Cache\RateLimiting\Limit::none()
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('ai-score', function (\Illuminate\Http\Request $request) {
            return $request->user()?->isAdmin()
                ? \Illuminate\Cache\RateLimiting\Limit::none()
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
