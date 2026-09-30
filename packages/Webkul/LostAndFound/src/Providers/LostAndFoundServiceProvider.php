<?php

namespace Webkul\LostAndFound\Providers;

use Illuminate\Support\ServiceProvider;

class LostAndFoundServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(
            \Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract::class,
            \Webkul\LostAndFound\Services\PublicLostAndFoundService::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'lost_found');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'lost_found');

        $this->loadRoutesFrom(__DIR__.'/../Routes/student-routes.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/employee-routes.php');
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/filesystems.php',
            'filesystems.disks',
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/acl.php',
            'acl',
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/lost_found.php',
            'lost_found',
        );
    }
}
