<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

// this class registers a helpful function I use in my blade templates to get a color value
// from a string. Mostly I use it to get a color value for ticker symbols
class BladeServiceProvider extends ServiceProvider
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
        Blade::directive('stringtohex', function (string $string) {
            return "<?php echo substr(md5($string), 0, 6); ?>";
        });
    }
}
