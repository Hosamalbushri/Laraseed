<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Web\Http\Controllers\HomeController;

uses(DatabaseTransactions::class);

it('assigns the generic root to exactly one Web Foundation route', function () {
    $rootRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => $route->uri() === '/' && in_array('GET', $route->methods(), true))
        ->values();

    expect($rootRoutes)->toHaveCount(1);

    $root = $rootRoutes->first();

    expect($root->getName())->toBe('web.home')
        ->and($root->getActionName())->toBe(HomeController::class.'@index')
        ->and($root->middleware())->toBe(['web', 'web_context'])
        ->and(route('web.home', absolute: false))->toBe('/');
});

it('serves the public root to an unauthenticated English guest through Base', function () {
    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $isWebsiteEnabled = in_array('website', config('campushub.optional_packages.enabled', []), true);

    $response->assertOk()
        ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false)
        ->assertDontSee('student-login', false);

    if ($isWebsiteEnabled) {
        $response->assertSee('website-hero', false);
    } else {
        $response->assertSee('web-home__empty', false);
    }

    expect(app(WebContextContract::class)->locale())->toBe('en')
        ->and(app(WebContextContract::class)->direction())->toBe('ltr')
        ->and(app(WebContextContract::class)->activeTheme())->toBe('base');
});

it('serves the public root in Arabic RTL through the same Base Theme', function () {
    $response = $this->withSession(['web_locale' => 'ar'])->get('/');

    $isWebsiteEnabled = in_array('website', config('campushub.optional_packages.enabled', []), true);

    $response->assertOk()
        ->assertSee('<html lang="ar" dir="rtl" data-theme="base">', false);

    if ($isWebsiteEnabled) {
        $response->assertSee('website-hero', false);
    } else {
        $response->assertSee('web-home__empty', false);
    }

    expect(app(WebContextContract::class)->locale())->toBe('ar')
        ->and(app(WebContextContract::class)->direction())->toBe('rtl')
        ->and(app(WebContextContract::class)->activeTheme())->toBe('base');
});

it('keeps the Web root source independent from optional packages and themes', function () {
    $rootRoutes = file_get_contents(base_path('routes/web.php'));
    $webRoutes = file_get_contents(base_path('packages/Webkul/Web/src/Routes/web-routes.php'));
    $homeController = file_get_contents(base_path('packages/Webkul/Web/src/Http/Controllers/HomeController.php'));

    expect($rootRoutes)->not->toContain('StudentSessionController')
        ->and($webRoutes)->toContain("Route::get('/', [HomeController::class, 'index'])")
        ->and($webRoutes)->toContain("->name('web.home')");

    foreach (['Webkul\\Student', 'Webkul\\Event', 'Webkul\\LostAndFound', 'Webkul\\Shop', 'Webkul\\Admin'] as $forbidden) {
        expect($webRoutes)->not->toContain($forbidden)
            ->and($homeController)->not->toContain($forbidden);
    }

    expect($webRoutes)->not->toContain('auth:student')
        ->not->toContain('admin_locale');
});
