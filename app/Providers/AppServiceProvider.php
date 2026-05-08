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
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:262144'],
            'livewire.temporary_file_upload.max_upload_time' => 15,
        ]);

        App::setLocale('pt_BR');
    }
}
