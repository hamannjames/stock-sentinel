<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        seo()
            ->site('Stock Sentinel')
            ->title(
                default: 'Stock Sentinel - Transparent congressional stock transactions',
                modify: fn (string $title) => $title . ' | Stock Sentinel'
            )
            ->description(default: 'Ever wonder what stocks your elected reps are invested in? Now you can know!')
            ->image(default: fn () => url('/static/images/logo/stocksentinellogo.png'));
    }
}
