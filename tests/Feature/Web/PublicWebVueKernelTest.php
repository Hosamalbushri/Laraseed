<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    Route::get('_test/public-web-component-showcase', function () {
        return View::file(base_path('tests/Fixtures/views/web-component-showcase.blade.php'));
    })->middleware(['web', 'web_context']);
});

it('renders the test-only showcase through public Blade component APIs', function () {
    $source = file_get_contents(base_path('tests/Fixtures/views/web-component-showcase.blade.php'));
    $response = $this->withSession(['web_locale' => 'en'])->get('_test/public-web-component-showcase');

    expect($source)->toContain('<x-web::button>')
        ->toContain('<x-web::card')
        ->toContain('<x-web::badge')
        ->toContain('<x-web::alert')
        ->toContain('<x-web::form.field')
        ->toContain('<x-web::accordion')
        ->toContain('<x-web::modal')
        ->toContain('<x-web::drawer')
        ->toContain('<x-web::dropdown');

    $response->assertOk()
        ->assertSee('<html lang="en" dir="ltr"', false)
        ->assertSee('data-web-component-showcase', false)
        ->assertSee('data-showcase-server-content', false)
        ->assertSee('This content is rendered by Laravel before Vue initializes.')
        ->assertSee('id="app"', false)
        ->assertSee('<v-web-accordion', false)
        ->assertSee('id="showcase-accordion-one"', false)
        ->assertSee('id="showcase-accordion-two"', false)
        ->assertSee('data-web-accordion-always-open="false"', false)
        ->assertSee('data-web-accordion-always-open="true"', false)
        ->assertSee('First answer remains server rendered.')
        ->assertSee('Fourth answer can stay open with the third.')
        ->assertSee('<v-web-modal', false)
        ->assertSee('id="showcase-modal-one"', false)
        ->assertSee('id="showcase-modal-two"', false)
        ->assertSee('<v-web-drawer', false)
        ->assertSee('id="showcase-drawer-start"', false)
        ->assertSee('id="showcase-drawer-end"', false)
        ->assertSee('<v-web-dropdown', false)
        ->assertSee('id="showcase-dropdown-one"', false)
        ->assertSee('id="showcase-dropdown-two"', false);
});

it('renders the same showcase in RTL without a separate component implementation', function () {
    $this->withSession(['web_locale' => 'ar'])
        ->get('_test/public-web-component-showcase')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl"', false)
        ->assertSee('id="showcase-accordion-one"', false)
        ->assertSee('id="showcase-accordion-two"', false)
        ->assertSee('id="showcase-modal-one"', false)
        ->assertSee('id="showcase-drawer-start"', false)
        ->assertSee('id="showcase-dropdown-one"', false);
});

it('uses one deterministic compiler-enabled Vue bootstrap and registration path', function () {
    $assetPath = base_path('packages/Webkul/Web/src/Resources/assets/js');
    $sources = collect((new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetPath))))
        ->filter(fn ($file) => $file->isFile() && $file->getExtension() === 'js')
        ->mapWithKeys(fn ($file) => [$file->getPathname() => file_get_contents($file->getPathname())]);
    $combined = $sources->implode("\n");

    expect(substr_count($combined, 'createApp('))->toBe(1)
        ->and($combined)->toContain("vue/dist/vue.esm-bundler.js")
        ->toContain("app.component('v-web-accordion', WebAccordion)")
        ->toContain("app.component('v-web-modal', WebModal)")
        ->toContain("app.component('v-web-drawer', WebDrawer)")
        ->toContain("app.component('v-web-dropdown', WebDropdown)")
        ->toContain('expandedPanelIds')
        ->toContain("event.key === 'Escape'")
        ->not->toContain('createRouter(')
        ->not->toContain('createPinia(')
        ->not->toContain('Webkul\\Website')
        ->not->toContain('Webkul\\Student')
        ->not->toContain('Webkul\\LostAndFound');
});

it('keeps the production manifest connected to theme CSS and the public Vue runtime', function () {
    $manifestPath = public_path('themes/base/build/manifest.json');

    expect(is_file($manifestPath))->toBeTrue();

    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $cssEntry = 'assets/css/theme.css';
    $jsEntry = '../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js';

    expect($manifest)->toHaveKeys([$cssEntry, $jsEntry]);

    $cssFile = public_path('themes/base/build/'.$manifest[$cssEntry]['file']);
    $jsFile = public_path('themes/base/build/'.$manifest[$jsEntry]['file']);

    expect(is_file($cssFile))->toBeTrue()
        ->and(is_file($jsFile))->toBeTrue()
        ->and(filesize($cssFile))->toBeGreaterThan(1000)
        ->and(filesize($jsFile))->toBeGreaterThan(10000)
        ->and(file_get_contents($cssFile))->toContain('.web-accordion')
        ->and(file_get_contents($jsFile))->toContain('v-web-accordion')
        ->and(file_get_contents($jsFile))->toContain('v-web-modal')
        ->and(file_get_contents($jsFile))->toContain('v-web-drawer')
        ->and(file_get_contents($jsFile))->toContain('v-web-dropdown')
        ->and(file_get_contents($jsFile))->toContain('data-web-accordion-trigger');
});

it('keeps generated IDs collision safe across independent instances', function () {
    $response = $this->get('_test/public-web-component-showcase');
    $html = $response->getContent();

    preg_match_all('/id="([^"]+)"/', $html, $matches);

    expect($matches[1])->not->toBeEmpty()
        ->and(count($matches[1]))->toBe(count(array_unique($matches[1])));
});
