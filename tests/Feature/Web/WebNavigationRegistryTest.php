<?php

use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Navigation\NavigationRegistry;

beforeEach(function () {
    $this->registry = new NavigationRegistry;
});
it('registers and resolves navigation items for supported locations', function () {
    $this->registry->register([
        'id' => 'home',
        'title' => 'Home',
        'url' => '/',
        'location' => 'header',
        'order' => 10,
    ]);

    $this->registry->register([
        'id' => 'privacy',
        'title' => 'Privacy',
        'url' => '/privacy',
        'location' => 'footer',
        'order' => 10,
    ]);

    expect($this->registry->has('home', 'header'))->toBeTrue();
    expect($this->registry->has('home', 'footer'))->toBeFalse();
    expect($this->registry->has('privacy', 'footer'))->toBeTrue();

    $headerItems = $this->registry->getItems('header');
    expect($headerItems)->toHaveCount(1);
    expect($headerItems->first()->id)->toBe('home');

    $footerItems = $this->registry->getItems('footer');
    expect($footerItems)->toHaveCount(1);
    expect($footerItems->first()->id)->toBe('privacy');
});

it('enforces deterministic ordering by order then by id', function () {
    $this->registry->register([
        'id' => 'events',
        'title' => 'Events',
        'url' => '/events',
        'location' => 'header',
        'order' => 20,
    ]);

    $this->registry->register([
        'id' => 'home',
        'title' => 'Home',
        'url' => '/',
        'location' => 'header',
        'order' => 10,
    ]);

    $this->registry->register([
        'id' => 'about',
        'title' => 'About',
        'url' => '/about',
        'location' => 'header',
        'order' => 20,
    ]);

    $items = $this->registry->getItems('header');

    expect($items)->toHaveCount(3);
    expect($items[0]->id)->toBe('home'); // order 10
    expect($items[1]->id)->toBe('about'); // order 20, 'about' < 'events'
    expect($items[2]->id)->toBe('events'); // order 20, 'events'
});

it('rejects duplicate navigation IDs within the same location', function () {
    $this->registry->register([
        'id' => 'home',
        'title' => 'Home',
        'url' => '/',
        'location' => 'header',
    ]);

    $this->registry->register([
        'id' => 'home',
        'title' => 'Home Duplicate',
        'url' => '/duplicate',
        'location' => 'header',
    ]);
})->throws(InvalidArgumentException::class);

it('rejects invalid navigation locations', function () {
    $this->registry->register([
        'id' => 'bad',
        'title' => 'Bad',
        'url' => '/bad',
        'location' => 'invalid_sidebar',
    ]);
})->throws(InvalidArgumentException::class);

it('builds a hierarchical navigation tree with parent-child relationships', function () {
    $this->registry->register([
        'id' => 'services',
        'title' => 'Services',
        'url' => '/services',
        'location' => 'header',
        'order' => 10,
    ]);

    $this->registry->register([
        'id' => 'service_a',
        'title' => 'Service A',
        'url' => '/services/a',
        'location' => 'header',
        'parent_id' => 'services',
        'order' => 1,
    ]);

    $this->registry->register([
        'id' => 'service_b',
        'title' => 'Service B',
        'url' => '/services/b',
        'location' => 'header',
        'parent_id' => 'services',
        'order' => 2,
    ]);

    $tree = $this->registry->getTree('header');

    expect($tree)->toHaveCount(1);
    expect($tree->first()->id)->toBe('services');
    expect($tree->first()->children)->toHaveCount(2);
    expect($tree->first()->children[0]->id)->toBe('service_a');
    expect($tree->first()->children[1]->id)->toBe('service_b');
});

it('respects conditional visibility for navigation items', function () {
    $this->registry->register([
        'id' => 'visible_item',
        'title' => 'Visible',
        'url' => '/visible',
        'location' => 'header',
        'visible' => true,
    ]);

    $this->registry->register([
        'id' => 'hidden_item',
        'title' => 'Hidden',
        'url' => '/hidden',
        'location' => 'header',
        'visible' => false,
    ]);

    $this->registry->register([
        'id' => 'closure_item',
        'title' => 'Closure',
        'url' => '/closure',
        'location' => 'header',
        'visible' => fn () => false,
    ]);

    $items = $this->registry->getItems('header');

    expect($items)->toHaveCount(1);
    expect($items->first()->id)->toBe('visible_item');
});

it('neutralizes unsafe executable URL schemes at registration time', function () {
    foreach ([
        'javascript:alert(1)',
        'JAVASCRIPT:alert(document.cookie)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
        'file:///etc/passwd',
    ] as $index => $unsafeUrl) {
        $this->registry->register([
            'id' => "unsafe_{$index}",
            'title' => "Unsafe {$index}",
            'url' => $unsafeUrl,
            'location' => 'header',
        ]);
    }

    $items = $this->registry->getItems('header');

    foreach ($items as $item) {
        expect($item->url)->toBe('#');
    }
});

it('resolves active navigation state accurately across root, section prefixes, and route patterns', function () {
    $this->registry->register([
        'id' => 'nav_home',
        'title' => 'Home',
        'url' => '/',
        'location' => 'header',
        'order' => 10,
    ]);

    $this->registry->register([
        'id' => 'nav_directory',
        'title' => 'Directory',
        'url' => '/directory',
        'location' => 'header',
        'order' => 20,
    ]);

    $this->registry->register([
        'id' => 'nav_custom_pattern',
        'title' => 'Catalog',
        'url' => '/catalog',
        'location' => 'header',
        'order' => 30,
        'attributes' => [
            'active_patterns' => ['catalog', 'items/*'],
        ],
    ]);

    $items = $this->registry->getItems('header')->keyBy('id');

    $rootRequest = \Illuminate\Http\Request::create('/', 'GET');
    expect($items['nav_home']->isActive($rootRequest))->toBeTrue()
        ->and($items['nav_directory']->isActive($rootRequest))->toBeFalse()
        ->and($items['nav_custom_pattern']->isActive($rootRequest))->toBeFalse();

    $dirIndexRequest = \Illuminate\Http\Request::create('/directory', 'GET');
    expect($items['nav_home']->isActive($dirIndexRequest))->toBeFalse()
        ->and($items['nav_directory']->isActive($dirIndexRequest))->toBeTrue();

    $dirChildRequest = \Illuminate\Http\Request::create('/directory/entry-42', 'GET');
    expect($items['nav_home']->isActive($dirChildRequest))->toBeFalse()
        ->and($items['nav_directory']->isActive($dirChildRequest))->toBeTrue();

    $dirSiblingCollisionRequest = \Illuminate\Http\Request::create('/directory-other', 'GET');
    expect($items['nav_directory']->isActive($dirSiblingCollisionRequest))->toBeFalse();

    $patternRequest = \Illuminate\Http\Request::create('/items/REF-100', 'GET');
    expect($items['nav_custom_pattern']->isActive($patternRequest))->toBeTrue();
});
