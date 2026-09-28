<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\CoreConfig;
use Webkul\Event\Models\Event;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Web\Contracts\NavigationRegistryContract;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Event::query()->update(['status' => false]);
});

function eventWebRecord(array $overrides = []): Event
{
    return Event::query()->create([
        'title' => 'Public Event '.uniqid(),
        'event_date' => now()->addDay()->toDateString(),
        'event_end_date' => now()->addDays(2)->toDateString(),
        'organizer' => 'CampusHub',
        'available_seats' => null,
        'availability_use_seats' => false,
        'availability_use_end_date' => true,
        'image' => null,
        'description' => 'A public event description.',
        'status' => true,
        ...$overrides,
    ]);
}

it('serves a bounded public Event index and detail through Base in English', function () {
    $event = eventWebRecord([
        'title' => 'Open Campus Day',
        'event_date' => '2030-04-10',
        'event_end_date' => '2030-04-11',
    ]);

    $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false)
        ->assertSee('data-event-web-index', false)
        ->assertSee('Open Campus Day')
        ->assertSee('>Events</a>', false)
        ->assertSee('web-card', false);

    $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.show', $event->id))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false)
        ->assertSee('data-event-web-show', false)
        ->assertSee('<time datetime="2030-04-10">2030-04-10</time>', false)
        ->assertSee('Open Campus Day');
});

it('renders a translated accessible empty state', function () {
    $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('role="status"', false)
        ->assertSee('No events available')
        ->assertSee('There are no public events available right now.');
});

it('applies the complete public visibility policy to the listing', function () {
    eventWebRecord(['title' => 'Visible Event']);
    eventWebRecord(['title' => 'Unpublished Event', 'status' => false]);
    eventWebRecord([
        'title' => 'Sold Out Event',
        'availability_use_seats' => true,
        'available_seats' => 0,
    ]);
    eventWebRecord([
        'title' => 'Expired Event',
        'event_end_date' => now()->subDay()->toDateString(),
        'availability_use_end_date' => true,
    ]);

    $this->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('Visible Event')
        ->assertDontSee('Unpublished Event')
        ->assertDontSee('Sold Out Event')
        ->assertDontSee('Expired Event');
});

it('returns 404 for every missing or non-public Event detail', function () {
    $unpublished = eventWebRecord(['status' => false]);
    $soldOut = eventWebRecord([
        'availability_use_seats' => true,
        'available_seats' => 0,
    ]);
    $expired = eventWebRecord([
        'event_end_date' => now()->subDay()->toDateString(),
        'availability_use_end_date' => true,
    ]);

    foreach ([$unpublished->id, $soldOut->id, $expired->id, 999999999] as $id) {
        $this->get(route('event.web.show', $id))->assertNotFound();
    }
});

it('paginates public Events using the bounded Event setting', function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.store.events_page.per_page'],
        ['value' => '12'],
    );

    foreach (range(1, 13) as $number) {
        eventWebRecord(['title' => "Paginated Event {$number}"]);
    }

    $firstPage = $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index'));
    $firstPage->assertOk()->assertSee('Page 1 of 2');

    expect(substr_count($firstPage->getContent(), 'data-event-card='))->toBe(12);

    $secondPage = $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index', ['page' => 2]));
    $secondPage->assertOk()->assertSee('Page 2 of 2');

    expect(substr_count($secondPage->getContent(), 'data-event-card='))->toBe(1);
});

it('escapes Event content and SEO while using canonical public gallery images', function () {
    Storage::fake('public');

    $event = eventWebRecord([
        'title' => 'Visual <Event>',
        'description' => '<script>alert("unsafe")</script>',
        'image' => 'events/legacy-projection.jpg',
    ]);
    $event->images()->create([
        'path' => 'events/canonical.jpg',
        'position' => 0,
    ]);

    $response = $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.show', $event->id));

    $response->assertOk()
        ->assertSee('<title>Visual &lt;Event&gt; | CampusHub</title>', false)
        ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("unsafe")</script>', false)
        ->assertSee('events/canonical.jpg')
        ->assertSee('alt="Visual &lt;Event&gt; image 1"', false)
        ->assertDontSee('events/legacy-projection.jpg');
});

it('renders actual Event navigation and pages safely across en to ar to en', function () {
    $registry = app(NavigationRegistryContract::class);
    $registryId = spl_object_id($registry);

    $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('>Events</a>', false)
        ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false);

    $this->withSession(['web_locale' => 'ar'])
        ->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('>الفعاليات</a>', false)
        ->assertSee('<html lang="ar" dir="rtl" data-theme="base">', false);

    $this->withSession(['web_locale' => 'en'])
        ->get(route('event.web.index'))
        ->assertOk()
        ->assertSee('>Events</a>', false)
        ->assertDontSee('>الفعاليات</a>', false);

    expect(spl_object_id(app(NavigationRegistryContract::class)))->toBe($registryId);
});

it('allows a test Theme to override the Event index generically', function () {
    $fixtureRoot = sys_get_temp_dir().'/event_web_theme_'.uniqid();
    $fixtureViews = $fixtureRoot.'/views/overrides/event/web';
    mkdir($fixtureViews, 0777, true);

    file_put_contents($fixtureViews.'/index.blade.php', <<<'BLADE'
@extends('web::layouts.master')

@section('content')
    <p data-event-theme-fixture>Event fixture override</p>
@endsection
BLADE);

    try {
        app(ThemeRegistryContract::class)->register(new ThemeDefinition(
            id: 'event-fixture',
            name: 'Event Fixture',
            basePath: $fixtureRoot,
            parent: 'base',
            viewsPath: $fixtureRoot.'/views',
        ));
        app(ThemeResolverContract::class)->setActiveTheme('event-fixture');

        $this->get(route('event.web.index'))
            ->assertOk()
            ->assertSee('data-theme="base"', false)
            ->assertSee('data-event-theme-fixture', false)
            ->assertSee('Event fixture override');
    } finally {
        (new Filesystem)->deleteDirectory($fixtureRoot);
    }
});
