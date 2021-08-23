<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Helpers\Connectors\EfdConnector;
use App\Http\Helpers\Processors\PtrProcessor;
use App\Http\Helpers\Connectors\ProPublicaConnector;
use App\Http\Helpers\Processors\EfdTransactionProcessor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // I use the service provider to make sure I create connectors and processors the same way
        // across the app
        $this->app->bind(PtrProcessor::class, function ($app) {
            return new PtrProcessor(new EfdConnector(), new EfdTransactionProcessor());
        });

        $this->app->bind(ProPublicaConnector::class, function ($app) {
            return new ProPublicaConnector();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
