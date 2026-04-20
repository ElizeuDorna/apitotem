<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
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
        config([
            'app.locale' => 'pt_BR',
            'app.fallback_locale' => 'pt_BR',
        ]);

        App::setLocale('pt_BR');
    }
}
