<?php

namespace Webkul\Website\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Navigation\NavigationLabel;
use Webkul\Web\Navigation\NavigationLabelResolver;
use Webkul\Web\Seo\SeoService;
use Webkul\Website\Contracts\SiteDefinitionContract;
use Webkul\Website\SiteDefinition\SiteDefinition;
use Webkul\Website\SiteDefinition\SiteDefinitionResolver;

class WebsiteServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/website.php', 'website');

        $this->app->singleton(SiteDefinitionContract::class, SiteDefinitionResolver::class);
        $this->app->singleton(SiteDefinitionResolver::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'website');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'website');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web-routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Resources/assets' => public_path('vendor/website'),
            ], 'website-assets');
        }

        $this->registerSiteDefinitionPresentation();

        $this->registerHomepageSections();

        $this->registerNavigationItems();

        $this->registerIntegrations();
    }

    /**
     * Wire request-locale-safe SiteDefinition into SEO defaults, Website views, and Web master layout header/footer slots.
     */
    protected function registerSiteDefinitionPresentation(): void
    {
        if ($this->app->bound(SeoService::class)) {
            $this->app->make(SeoService::class)->setDefaultsResolver(function (): array {
                $site = $this->app->make(SiteDefinitionContract::class)->current();

                return [
                    'site_name'           => $site->seoSiteName,
                    'default_title'       => $site->seoDefaultTitle,
                    'default_description' => $site->seoDefaultDescription,
                    'default_image'       => $site->seoDefaultImageUrl,
                ];
            });
        }

        View::composer('website::*', function ($view): void {
            $data = $view->getData();

            if (! array_key_exists('siteDefinition', $data) || ! $data['siteDefinition'] instanceof SiteDefinition) {
                $view->with('siteDefinition', $this->app->make(SiteDefinitionContract::class)->current());
            }
        });

        View::composer('web::layouts.master', function ($view): void {
            $siteDefinition = $this->app->make(SiteDefinitionContract::class)->current();
            $navigation = $this->app->bound(NavigationRegistryContract::class)
                ? $this->app->make(NavigationRegistryContract::class)
                : null;
            $navigationLabels = $this->app->bound(NavigationLabelResolver::class)
                ? $this->app->make(NavigationLabelResolver::class)
                : null;

            $headerItems = $navigation ? $navigation->getItems('header') : collect();
            $footerItems = $navigation ? $navigation->getItems('footer') : collect();

            $view->with('siteDefinition', $siteDefinition);
            $view->with('webFaviconUrl', $siteDefinition->faviconUrl);

            $factory = $view->getFactory();

            if (! $factory->hasSection('header')) {
                $factory->startSection(
                    'header',
                    $factory->make('website::partials.header', [
                        'siteDefinition'   => $siteDefinition,
                        'headerItems'      => $headerItems,
                        'navigationLabels' => $navigationLabels,
                    ])
                );
            }

            if (! $factory->hasSection('footer')) {
                $factory->startSection(
                    'footer',
                    $factory->make('website::partials.footer', [
                        'siteDefinition'   => $siteDefinition,
                        'footerItems'      => $footerItems,
                        'navigationLabels' => $navigationLabels,
                    ])
                );
            }
        });
    }

    /**
     * Conditionally register integrations with optional business packages based on explicit composition.
     */
    protected function registerIntegrations(): void
    {
        $composition = $this->app->bound(\Webkul\Core\Packages\OptionalPackageComposition::class)
            ? $this->app->make(\Webkul\Core\Packages\OptionalPackageComposition::class)
            : null;

        if ($composition && in_array('lost_and_found', $composition->enabledPackages(), true)) {
            $this->app->register(\Webkul\Website\Integrations\LostAndFound\WebsiteLostAndFoundServiceProvider::class);
        }
    }

    /**
     * Register site-specific sections on the public home page.
     */
    protected function registerHomepageSections(): void
    {
        if (! $this->app->bound(SectionRegistryContract::class)) {
            return;
        }

        $sections = $this->app->make(SectionRegistryContract::class);

        $sections->register([
            'page'  => 'home',
            'key'   => 'website_hero',
            'order' => 10,
            'view'  => 'website::sections.hero',
            'data'  => [],
        ]);

        $sections->register([
            'page'  => 'home',
            'key'   => 'website_features',
            'order' => 20,
            'view'  => 'website::sections.features',
            'data'  => [],
        ]);

        $sections->register([
            'page'  => 'home',
            'key'   => 'website_announcements',
            'order' => 30,
            'view'  => 'website::sections.announcements',
            'data'  => [],
        ]);
    }

    /**
     * Register site-specific navigation items.
     */
    protected function registerNavigationItems(): void
    {
        if (! $this->app->bound(NavigationRegistryContract::class)) {
            return;
        }

        $navigation = $this->app->make(NavigationRegistryContract::class);

        $navigation->register([
            'id'         => 'website_home',
            'title'      => NavigationLabel::translation('website::app.nav.home'),
            'url'        => '/',
            'location'   => 'header',
            'order'      => 10,
            'attributes' => [
                'active_routes'   => ['web.home'],
                'active_patterns' => ['/'],
            ],
        ]);

        $navigation->register([
            'id'         => 'website_about',
            'title'      => NavigationLabel::translation('website::app.nav.about'),
            'url'        => '/about',
            'location'   => 'header',
            'order'      => 20,
            'attributes' => [
                'active_routes'   => ['website.about'],
                'active_patterns' => ['about', 'about/*'],
            ],
        ]);

        $navigation->register([
            'id'         => 'website_footer_home',
            'title'      => NavigationLabel::translation('website::app.nav.home'),
            'url'        => '/',
            'location'   => 'footer',
            'order'      => 5,
            'attributes' => [
                'active_routes'   => ['web.home'],
                'active_patterns' => ['/'],
            ],
        ]);

        $navigation->register([
            'id'         => 'website_footer_about',
            'title'      => NavigationLabel::translation('website::app.nav.about'),
            'url'        => '/about',
            'location'   => 'footer',
            'order'      => 10,
            'attributes' => [
                'active_routes'   => ['website.about'],
                'active_patterns' => ['about', 'about/*'],
            ],
        ]);
    }
}
