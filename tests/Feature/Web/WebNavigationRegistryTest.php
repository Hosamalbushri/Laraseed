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
