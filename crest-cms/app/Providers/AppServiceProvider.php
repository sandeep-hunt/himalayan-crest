<?php

namespace App\Providers;

use App\Support\Locales;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Add the languages created in Admin → Settings → Languages to the built-in list
        Locales::boot();
    }
}
