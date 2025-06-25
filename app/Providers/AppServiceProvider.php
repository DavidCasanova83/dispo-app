<?php

namespace App\Providers;

use App\Repositories\AccommodationRepository;
use App\Repositories\AccommodationRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AccommodationRepositoryInterface::class, AccommodationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
