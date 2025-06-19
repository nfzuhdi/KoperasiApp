<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;

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
public function boot()
    {
        if (app()->environment(['local', 'production']) && str_contains(config('app.url'), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
