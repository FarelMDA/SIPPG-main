<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Memungkinkan `<x-layouts.app title="...">...</x-layouts.app>` dipakai
        // di halaman komposit non-Livewire (mis. master-data/struktur-organisasi.blade.php)
        // yang menggabungkan beberapa Livewire component sekaligus.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        // Dibutuhkan oleh middleware `throttleApi()` (bootstrap/app.php) yang
        // diterapkan ke routes/api.php (/api/v1/sync/*, SRS §2.1, §13).
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
