<?php

namespace Noprotocol\LaravelLocation;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel)
    {

        $this->publishConfig();

        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'location');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('location', function()
        {
            return new \Noprotocol\LaravelLocation\Classes\Facades\Location;
        });
    }


    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/config/location.php' => config_path('location.php'),
        ], 'location');
    }
}
