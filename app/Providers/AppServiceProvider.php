<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('registration', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(5)->by($request->ip().'|'.strtolower((string) $request->input('email'))));
        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // Force CORS headers for API requests.
        // This is necessary because your CORS middleware must run even when
        // exceptions happen (like 500 responses).
        $this->app->afterResolving(\Illuminate\Routing\Router::class, function ($router) {
            $router->middlewareGroup('api', array_merge($router->getMiddlewareGroup('api') ?? [], [
                \App\Http\Middleware\Cors::class,
            ]));
        });

        ResetPassword::createUrlUsing(function ($user, string $token){
            return env("FRONTEND_URL")."/reset-password"."?token={$token}"."&email={$user->email}";
        });
    }
}
