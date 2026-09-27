<?php

namespace Webkul\Shop\Providers;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Shop\Repositories\ShopThemeCustomizationRepository;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(ShopThemeCustomizationRepository::class, function ($app) {
            return new ShopThemeCustomizationRepository($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $router->middlewareGroup('shop', [
            'admin_locale',
        ]);

        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'shop');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'shop');

        Paginator::defaultView('shop::partials.pagination');

        Paginator::defaultSimpleView('shop::partials.pagination');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'shop');

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/shop.php',
            'shop'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.student'
        );
    }
}
