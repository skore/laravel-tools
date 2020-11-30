<?php

namespace SkoreLabs\LaravelTools;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            'SkoreLabs\LaravelTools\Commands\CheckPublishablesCommand'
        ]);
    }
}
