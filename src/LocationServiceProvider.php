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

        // $this->loadViewsFrom(__DIR__.'/resources/views', 'hack'); // add with the namespace "cms"

        // $this->publishes([
        //     __DIR__.'/resources/views/cms' => resource_path('views/cms'),
        //     __DIR__.'/resources/views/1' => resource_path('views/1'),
        //     __DIR__.'/resources/views/offline.blade.php' => resource_path('views/offline.blade.php'),
        // ], 'hack');

        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'cms');
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
            return new \Noprotocol\LaravelLocation\Facades\Location;
        });
    }


    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/config/location.php' => config_path('location.php'),
        ], 'location');
    }
}
