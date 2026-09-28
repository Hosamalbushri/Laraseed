<?php

namespace Webkul\Web\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Web\Context\WebContext;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Web\Http\Middleware\ResolveWebLocale;
use Webkul\Web\Navigation\NavigationLabelResolver;
use Webkul\Web\Navigation\NavigationRegistry;
use Webkul\Web\Sections\SectionRegistry;
use Webkul\Web\Seo\SeoService;

class WebServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(NavigationRegistryContract::class, NavigationRegistry::class);
        $this->app->singleton(NavigationRegistry::class);
        $this->app->bind(NavigationLabelResolver::class);

        $this->app->singleton(SectionRegistryContract::class, SectionRegistry::class);
        $this->app->singleton(SectionRegistry::class);

        $this->app->singleton(SeoMetadataContract::class, SeoService::class);
        $this->app->singleton(SeoService::class);

        $this->app->scoped(WebContextContract::class, function ($app) {
            return new WebContext(
                locale: app()->getLocale(),
                direction: in_array(app()->getLocale(), ['ar', 'fa', 'he'], true) ? 'rtl' : 'ltr',
                activeTheme: $app->make(ThemeResolverContract::class)->resolveActiveTheme()->id,
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('web_locale', ResolveWebLocale::class);
        $router->aliasMiddleware('web_context', ResolveWebLocale::class);

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'web');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'web');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web-routes.php');
    }
}
