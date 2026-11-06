<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Shipped with Git for Windows; used only when PHP itself has no CA bundle configured. */
    private const GIT_FOR_WINDOWS_CA_BUNDLE = 'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt';

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
        // Windows PHP has no default CA store, so every outbound HTTPS call (Telegram, CBU, Groq) fails
        // with cURL error 60 unless php.ini sets curl.cainfo. Linux servers use the system store.
        $bundle = env('HTTP_CA_BUNDLE') ?: (PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') ? self::GIT_FOR_WINDOWS_CA_BUNDLE : null);
        if ($bundle && is_file($bundle)) {
            Http::globalOptions(['verify' => $bundle]);
        }
    }
}
