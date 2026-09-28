<?php

use Illuminate\Support\Facades\Route;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Web\Navigation\NavigationRegistry;
use Webkul\Web\Sections\SectionRegistry;
use Webkul\Web\Seo\SeoService;

it('registers Web Foundation services in the container', function () {
    expect(app(NavigationRegistryContract::class))->toBeInstanceOf(NavigationRegistry::class);
    expect(app(NavigationRegistry::class))->toBeInstanceOf(NavigationRegistry::class);

    expect(app(SectionRegistryContract::class))->toBeInstanceOf(SectionRegistry::class);
    expect(app(SectionRegistry::class))->toBeInstanceOf(SectionRegistry::class);

    expect(app(SeoMetadataContract::class))->toBeInstanceOf(SeoService::class);
    expect(app(SeoService::class))->toBeInstanceOf(SeoService::class);

    expect(app(WebContextContract::class))->toBeInstanceOf(WebContextContract::class);
});

it('ensures Web production source contains zero forbidden package references', function () {
    $srcDir = base_path('packages/Webkul/Web/src');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));

    $forbidden = [
        'Webkul\Admin',
        'Webkul\Student',
        'Webkul\Event',
        'Webkul\LostAndFound',
        'Webkul\Shop',
    ];

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getRealPath());

        foreach ($forbidden as $forbiddenNs) {
            expect($content)->not->toContain($forbiddenNs, "Forbidden namespace {$forbiddenNs} found in {$file->getRealPath()}");
        }
    }
});

it('ensures Web routes do not use Admin middleware or require employee authentication', function () {
    $routes = Route::getRoutes();

    $webRoute = $routes->getByName('web.locale.switch');
    expect($webRoute)->not->toBeNull();

    $middleware = $webRoute->gatherMiddleware();

    expect($middleware)->not->toContain('admin_locale');
    expect($middleware)->not->toContain('user');
    expect($middleware)->not->toContain('bouncer');
    expect($middleware)->toContain('web');
});

it('loads Web translations and views under their dedicated web namespaces', function () {
    expect(trans('web::app.home.title'))->not->toBe('web::app.home.title');
    expect(view()->exists('web::layouts.master'))->toBeTrue();
    expect(view()->exists('web::home.index'))->toBeTrue();
});
