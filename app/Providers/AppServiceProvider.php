<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        // Mendukung MySQL/MariaDB lama dengan batas indeks InnoDB 767 byte
        // ketika charset utf8mb4 digunakan.
        Schema::defaultStringLength(191);

        RateLimiter::for('integration', function (Request $request): Limit {
            $client = $request->attributes->get('integration_client', []);

            return Limit::perMinute(120)->by($client['source_system'] ?? $request->ip());
        });
    }
}
