<?php

use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Http\Controllers\HomeController;
use Webkul\Web\Sections\SectionRegistry;
use Webkul\Web\Seo\SeoService;

it('registers and retrieves composable sections with deterministic ordering', function () {
    $registry = new SectionRegistry;

    $registry->register([
        'page' => 'home',
        'key' => 'events_strip',
        'view' => 'web::home.index',
        'order' => 20,
    ]);

    $registry->register([
        'page' => 'home',
        'key' => 'hero',
        'view' => 'web::home.index',
        'order' => 10,
    ]);

    $registry->register([
        'page' => 'home',
        'key' => 'about_strip',
        'view' => 'web::home.index',
        'order' => 20,
    ]);

    expect($registry->has('home', 'hero'))->toBeTrue();
    expect($registry->has('home', 'nonexistent'))->toBeFalse();

    $sections = $registry->getSections('home');

    expect($sections)->toHaveCount(3);
    expect($sections[0]['key'])->toBe('hero');
    expect($sections[1]['key'])->toBe('about_strip');
    expect($sections[2]['key'])->toBe('events_strip');
});

it('rejects duplicate section keys', function () {
    $registry = new SectionRegistry;

    $registry->register([
        'page' => 'home',
        'key' => 'hero',
        'view' => 'web::home.index',
    ]);

    $registry->register([
        'page' => 'home',
        'key' => 'hero',
        'view' => 'web::home.index',
    ]);
})->throws(InvalidArgumentException::class);

it('manages SEO metadata and renders HTML head tags', function () {
    $seo = new SeoService;

    $seo->setTitle('Campus Events')
        ->setDescription('Discover all upcoming campus activities.')
        ->setCanonicalUrl('http://localhost/events')
        ->setMeta('og:image', 'http://localhost/images/og.png');

    expect($seo->getTitle())->toBe('Campus Events | CampusHub');
    expect($seo->getDescription())->toBe('Discover all upcoming campus activities.');
    expect($seo->getCanonicalUrl())->toBe('http://localhost/events');

    $html = $seo->renderHeadHtml();

    expect($html)->toContain('<title>Campus Events | CampusHub</title>');
    expect($html)->toContain('<meta name="description" content="Discover all upcoming campus activities.">');
    expect($html)->toContain('<link rel="canonical" href="http://localhost/events">');
    expect($html)->toContain('<meta property="og:image" content="http://localhost/images/og.png">');
});

it('renders the generic web home page shell through HomeController', function () {
    $controller = app(HomeController::class);
    $view = $controller->index();

    expect($view->name())->toBe('web::home.index');
    expect($view->getData())->toHaveKey('sections');
});
