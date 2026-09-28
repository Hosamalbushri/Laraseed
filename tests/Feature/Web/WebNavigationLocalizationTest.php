<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\ServiceProvider;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Navigation\NavigationLabel;

uses(DatabaseTransactions::class);

final class WebNavigationLocalizationFixtureProvider extends ServiceProvider
{
    public function boot(NavigationRegistryContract $navigation): void
    {
        $navigation->register([
            'id' => 'fixture-localized',
            'title' => NavigationLabel::translation('web::app.navigation.home'),
            'url' => '/fixture-localized',
            'location' => 'header',
            'order' => 10,
        ]);

        $navigation->register([
            'id' => 'fixture-static',
            'title' => 'Provider static label',
            'url' => '/fixture-static',
            'location' => 'header',
            'order' => 20,
        ]);

        $navigation->register([
            'id' => 'fixture-hidden',
            'title' => NavigationLabel::translation('web::app.navigation.home'),
            'url' => '/fixture-hidden',
            'location' => 'header',
            'order' => 5,
            'visible' => false,
        ]);
    }
}

beforeEach(function () {
    app()->register(WebNavigationLocalizationFixtureProvider::class);

    $this->navigation = app(NavigationRegistryContract::class);
    $this->navigationObjectId = spl_object_id($this->navigation);
});

it('stores provider-time label definitions without translating or weakening registry behavior', function () {
    $items = $this->navigation->getItems('header')
        ->filter(fn ($item): bool => str_starts_with($item->id, 'fixture-'))
        ->values();

    expect($items)->toHaveCount(2)
        ->and($items->pluck('id')->all())->toBe(['fixture-localized', 'fixture-static'])
        ->and($items->first()->title)->toBeInstanceOf(NavigationLabel::class)
        ->and($items->first()->title->translationKey)->toBe('web::app.navigation.home')
        ->and($items->last()->title)->toBe('Provider static label');
});

it('renders static and English translation-backed labels through the real Base page', function () {
    $this->withSession(['web_locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('Provider static label')
        ->assertSee('>Home</a>', false)
        ->assertDontSee('fixture-hidden');
});

it('renders Arabic translation-backed labels through the real Base page', function () {
    $this->withSession(['web_locale' => 'ar'])
        ->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl" data-theme="base">', false)
        ->assertSee('>الرئيسية</a>', false);
});

it('uses one singleton registry safely across en to ar to en requests', function () {
    $this->withSession(['web_locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('>Home</a>', false)
        ->assertDontSee('>الرئيسية</a>', false);

    expect(spl_object_id(app(NavigationRegistryContract::class)))
        ->toBe($this->navigationObjectId);

    $this->withSession(['web_locale' => 'ar'])
        ->get('/')
        ->assertOk()
        ->assertSee('>الرئيسية</a>', false)
        ->assertDontSee('>Home</a>', false);

    expect(spl_object_id(app(NavigationRegistryContract::class)))
        ->toBe($this->navigationObjectId);

    $this->withSession(['web_locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('>Home</a>', false)
        ->assertDontSee('>الرئيسية</a>', false);

    expect(spl_object_id(app(NavigationRegistryContract::class)))
        ->toBe($this->navigationObjectId)
        ->and($this->navigation->getItems('header')->first()->title)
        ->toBeInstanceOf(NavigationLabel::class);
});

it('uses one singleton registry safely across ar to en requests', function () {
    $this->withSession(['web_locale' => 'ar'])
        ->get('/')
        ->assertOk()
        ->assertSee('>الرئيسية</a>', false);

    $this->withSession(['web_locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('>Home</a>', false);

    expect(spl_object_id(app(NavigationRegistryContract::class)))
        ->toBe($this->navigationObjectId);
});

it('escapes a translated label and immutable replacements in Base', function () {
    app('translator')->addLines([
        'fixture.parameterized' => 'Fixture :name',
    ], 'en', 'navigation-fixture');

    $this->navigation->register([
        'id' => 'fixture-escaped',
        'title' => NavigationLabel::translation(
            'navigation-fixture::fixture.parameterized',
            ['name' => '<Campus & Hub>'],
        ),
        'url' => '/fixture-escaped',
        'location' => 'header',
        'order' => 30,
    ]);

    $this->withSession(['web_locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('Fixture &lt;Campus &amp; Hub&gt;', false)
        ->assertDontSee('Fixture <Campus & Hub>', false);
});

it('uses the translator missing-key behavior without mutating the definition', function () {
    $label = NavigationLabel::translation('navigation-fixture::fixture.missing');

    $this->navigation->register([
        'id' => 'fixture-missing',
        'title' => $label,
        'url' => '/fixture-missing',
        'location' => 'header',
        'order' => 30,
    ]);

    $this->withSession(['web_locale' => 'ar'])
        ->get('/')
        ->assertOk()
        ->assertSee('navigation-fixture::fixture.missing');

    expect($label->translationKey)->toBe('navigation-fixture::fixture.missing')
        ->and($label->replacements)->toBe([]);
});
