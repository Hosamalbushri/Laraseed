<?php

namespace Webkul\Website\Integrations\LostAndFound;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Navigation\NavigationLabel;

class WebsiteLostAndFoundServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any integration services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/lost-found-routes.php');

        if ($this->app->bound('router')) {
            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        }

        $this->registerHomepageSection();

        $this->registerNavigationItems();
    }

    /**
     * Register navigation links for Lost & Found in header and footer.
     */
    protected function registerNavigationItems(): void
    {
        if (! $this->app->bound(NavigationRegistryContract::class)) {
            return;
        }

        $navigation = $this->app->make(NavigationRegistryContract::class);

        $navigation->register([
            'id'         => 'website_lost_found',
            'title'      => NavigationLabel::translation('website::app.nav.lost_found'),
            'url'        => '/lost-found',
            'location'   => 'header',
            'order'      => 15,
            'attributes' => [
                'active_routes'   => ['website.lost_found.*'],
                'active_patterns' => ['lost-found', 'lost-found/*'],
            ],
        ]);

        $navigation->register([
            'id'         => 'website_footer_lost_found',
            'title'      => NavigationLabel::translation('website::app.nav.lost_found'),
            'url'        => '/lost-found',
            'location'   => 'footer',
            'order'      => 15,
            'attributes' => [
                'active_routes'   => ['website.lost_found.*'],
                'active_patterns' => ['lost-found', 'lost-found/*'],
            ],
        ]);
    }

    /**
     * Register the LostAndFound presentation section on the public homepage.
     */
    protected function registerHomepageSection(): void
    {
        if (! $this->app->bound(SectionRegistryContract::class)) {
            return;
        }

        $sections = $this->app->make(SectionRegistryContract::class);

        $sections->register([
            'page'  => 'home',
            'key'   => 'website_lost_found',
            'order' => 25,
            'view'  => 'website::sections.lost-found',
            'data'  => function (): array {
                try {
                    if (! $this->app->bound(PublicLostAndFoundReadContract::class)) {
                        return ['recentItems' => []];
                    }

                    $reader = $this->app->make(PublicLostAndFoundReadContract::class);

                    return [
                        'recentItems' => $reader->getRecentPublicFoundItems(6),
                    ];
                } catch (Throwable $e) {
                    Log::error('Website LostAndFound integration failed to load items: '.$e->getMessage());

                    return ['recentItems' => []];
                }
            },
        ]);
    }
}
