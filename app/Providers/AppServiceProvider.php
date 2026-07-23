<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
