<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Webkul\Event\Http\Controllers\Web\EventController;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Navigation\NavigationLabel;

it('owns the exact public Event route contract and navigation definitions', function () {
    $expected = [
        'event.web.index' => ['events', EventController::class.'@index'],
        'event.web.show' => ['events/{event}', EventController::class.'@show'],
    ];

    foreach ($expected as $name => [$uri, $action]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain('GET')
            ->and($route->uri())->toBe($uri)
            ->and($route->getActionName())->toBe($action)
            ->and($route->middleware())->toBe(['web', 'web_context']);
    }

    expect(collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => in_array($route->getName(), array_keys($expected), true)))
        ->toHaveCount(2);

    $navigation = app(NavigationRegistryContract::class);

    foreach (['header', 'mobile'] as $location) {
        $item = $navigation->getItems($location)->firstWhere('id', 'event.events');

        expect($item)->not->toBeNull()
            ->and($item->url)->toBe('/events')
            ->and($item->title)->toBeInstanceOf(NavigationLabel::class)
            ->and($item->title->translationKey)->toBe('event::web.navigation.events');
    }
});

it('keeps Event Web presentation free of Admin and view-side business operations', function () {
    $eventWebPaths = [
        base_path('packages/Webkul/Event/src/Http/Controllers/Web'),
        base_path('packages/Webkul/Event/src/Resources/views/web'),
        base_path('packages/Webkul/Event/src/Routes/web-routes.php'),
    ];
    $source = '';

    foreach ($eventWebPaths as $path) {
        if (is_file($path)) {
            $source .= file_get_contents($path)."\n";

            continue;
        }

        foreach ((new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path))) as $file) {
            if ($file->isFile()) {
                $source .= file_get_contents($file->getPathname())."\n";
            }
        }
    }

    foreach (['Webkul\\Admin', 'admin::', '<x-admin::', 'Event::query(', 'DB::', 'bouncer(', 'hasPermission('] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }

    expect($source)->toContain('<x-web::card', '<x-web::badge', '<x-web::alert', '<x-web::button');
});

it('preserves Event Web translation parity across every shipped locale', function () {
    $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? $key : $prefix.'.'.$key;
            $keys = is_array($value)
                ? [...$keys, ...$flatten($value, $path)]
                : [...$keys, $path];
        }

        sort($keys);

        return $keys;
    };

    $reference = $flatten(require base_path('packages/Webkul/Event/src/Resources/lang/en/web.php'));

    foreach (['ar', 'es', 'fa', 'pt_BR', 'tr', 'vi'] as $locale) {
        expect($flatten(require base_path("packages/Webkul/Event/src/Resources/lang/{$locale}/web.php")))
            ->toBe($reference, "Event Web translation keys differ for {$locale}");
    }

    expect(trans('event::web.navigation.events', locale: 'en'))->toBe('Events')
        ->and(trans('event::web.navigation.events', locale: 'ar'))->toBe('الفعاليات');
});

it('keeps Foundation and themes free of Event production knowledge', function () {
    $roots = [
        base_path('packages/Webkul/Web/src'),
        base_path('packages/Webkul/Theme/src'),
        base_path('themes/base'),
        base_path('packages/Webkul/Admin/src'),
        base_path('packages/Webkul/Core/src'),
        base_path('packages/Webkul/DataGrid/src'),
        base_path('packages/Webkul/Installer/src'),
        base_path('packages/Webkul/User/src'),
    ];
    $violations = [];

    foreach ($roots as $root) {
        foreach ((new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root))) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach (['Webkul\\Event', 'event::web', 'event.web.', 'event.events'] as $needle) {
                if (str_contains($contents, $needle)) {
                    $violations[] = $file->getPathname().': '.$needle;
                }
            }
        }
    }

    expect($violations)->toBe([]);
});

it('declares the one-way internal Event to Web dependency without a cycle', function () {
    $eventManifest = json_decode(
        file_get_contents(base_path('packages/Webkul/Event/composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $webManifest = json_decode(
        file_get_contents(base_path('packages/Webkul/Web/composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $themeManifest = json_decode(
        file_get_contents(base_path('packages/Webkul/Theme/composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($eventManifest['require']['webkul/web'] ?? null)->toBe('dev-main')
        ->and($webManifest['require'])->not->toHaveKey('webkul/event')
        ->and($themeManifest['require'] ?? [])->not->toHaveKey('webkul/web', 'webkul/event');
});
