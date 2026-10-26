<?php

namespace App\Providers;

use App\Interfaces\Litecard\LitecardTransport;
use App\Services\Litecard\HttpLitecardTransport;
use App\Services\Signer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LitecardTransport::class, HttpLitecardTransport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
