<?php

namespace Webkul\Theme\Providers;

use DirectoryIterator;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\Registry\ThemeRegistry;
use Webkul\Theme\Resolution\ThemeResolver;
use Webkul\Theme\View\ThemeViewFinder;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/themes.php', 'themes');

        $this->app->singleton(ThemeRegistryContract::class, function () {
            $registry = new ThemeRegistry();
            $this->discoverThemes($registry);

            return $registry;
        });

        $this->app->singleton(ThemeRegistry::class, function ($app) {
            return $app->make(ThemeRegistryContract::class);
        });

        $this->app->scoped(ThemeResolverContract::class, function ($app) {
            return new ThemeResolver(
                $app->make(ThemeRegistryContract::class),
                config('themes.active', 'default')
            );
        });

        $this->app->scoped(ThemeResolver::class, function ($app) {
            return $app->make(ThemeResolverContract::class);
        });

        $this->app->singleton('view.finder', function ($app) {
            return new ThemeViewFinder(
                $app['files'],
                $app['config']['view.paths']
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/themes.php' => config_path('themes.php'),
            ], 'theme-config');
        }
    }

    /**
     * Auto-discover themes declared in configured directories.
     */
    protected function discoverThemes(ThemeRegistryContract $registry): void
    {
        $paths = (array) config('themes.paths', []);

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (new DirectoryIterator($path) as $item) {
                if ($item->isDot() || ! $item->isDir()) {
                    continue;
                }

                $manifestPath = $item->getPathname().'/theme.json';

                if (file_exists($manifestPath) && is_readable($manifestPath)) {
                    try {
                        $theme = ThemeDefinition::fromManifestFile($manifestPath);
                        $registry->register($theme);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }
    }
}
