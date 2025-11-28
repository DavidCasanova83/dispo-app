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
        // Rate limiting pour le formulaire de commande public
        RateLimiter::for('order-form', function (Request $request) {
            // Limite plus stricte pour les POST (soumissions)
            if ($request->isMethod('POST')) {
                return Limit::perHour(5)->by($request->ip())->response(function () {
                    return response('Vous avez atteint la limite de soumissions. Veuillez réessayer plus tard.', 429);
                });
            }

            // Limite pour les consultations (GET)
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return response('Trop de requêtes. Veuillez patienter avant de réessayer.', 429);
            });
        });
    }
}
