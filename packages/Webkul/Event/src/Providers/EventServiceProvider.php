<?php

namespace Webkul\Event\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Event\Services\EventSubscriptionService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EventSubscriptionService::class);
        // $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        //
    }
}
