<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\View\ThemeViewFinder;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Contracts\WebContextContract;

beforeEach(function () {
    Route::get('_test/base-theme-page', function (
        SectionRegistryContract $sections,
        SeoMetadataContract $seo,
    ) {
        $seo->setTitle('Base Theme Test')
            ->setDescription('Neutral theme rendering verification.')
            ->setCanonicalUrl(url('_test/base-theme-page'))
            ->setMeta('robots', 'index,follow')
            ->setMeta('og:type', 'website');

        return view('web::home.index', [
            'sections' => $sections->getSections('home'),
        ]);
    })->middleware(['web', 'web_context']);

    Route::get('_test/base-theme-components', function () {
        return Blade::render(<<<'BLADE'
            <x-web::button variant="danger" size="lg" disabled>Button</x-web::button>
            <x-web::card><x-web::card.header>Header</x-web::card.header><x-web::card.content>Content</x-web::card.content><x-web::card.footer>Footer</x-web::card.footer></x-web::card>
            <x-web::badge variant="success">Badge</x-web::badge>
            <x-web::alert variant="warning" :dismissible="true">Alert</x-web::alert>
            <x-web::form.field id="email" label="Email" help="Help" error="Error" :required="true"><x-web::form.input id="email" name="email" :invalid="true" describedBy="email-help email-error" /></x-web::form.field>
            <x-web::accordion id="base-accordion"><x-web::accordion.item id="base-item" title="Question">Answer</x-web::accordion.item></x-web::accordion>
        BLADE);
    })->middleware(['web', 'web_context']);
});

it('discovers and resolves the root production Base Theme', function () {
    $registry = app(ThemeRegistryContract::class);
    $resolver = app(ThemeResolverContract::class);

    expect($registry->has('base'))->toBeTrue();

    $theme = $registry->get('base');
    expect($theme->id)->toBe('base')
        ->and($theme->parent)->toBeNull()
        ->and($theme->viewsPath)->toBe(realpath(base_path('themes/base/views')))
        ->and($theme->assetsPath)->toBe(realpath(base_path('themes/base/assets')))
        ->and($resolver->resolveActiveTheme()->id)->toBe('base')
        ->and($resolver->resolveActiveInheritanceChain())->toHaveCount(1);
});

it('renders a real English Web request through Base with SEO and isolated assets once', function () {
    $response = $this->withSession(['web_locale' => 'en'])->get('_test/base-theme-page');

    $response->assertOk()
        ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false)
        ->assertSee('<title>Base Theme Test | CampusHub</title>', false)
        ->assertSee('<meta name="description" content="Neutral theme rendering verification.">', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('<meta name="robots" content="index,follow">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('web-home__empty', false);

    $html = $response->getContent();
    preg_match_all('/<link[^>]+rel="stylesheet"[^>]+href="[^"]*\/themes\/base\/build\/assets\/theme-[^"]+\.css"[^>]*>/', $html, $cssLinks);
    preg_match_all('/<script[^>]+src="[^"]*\/themes\/base\/build\/assets\/web-interactions-[^"]+\.js"[^>]*><\/script>/', $html, $jsScripts);

    expect($cssLinks[0])->toHaveCount(1)
        ->and($jsScripts[0])->toHaveCount(1)
        ->and(app(WebContextContract::class)->activeTheme())->toBe('base');

    $finder = app('view.finder');
    expect($finder)->toBeInstanceOf(ThemeViewFinder::class)
        ->and($finder->getActiveThemeChain())->toHaveCount(1)
        ->and($finder->getActiveThemeChain()[0]->id)->toBe('base');
});

it('renders a real Arabic Web request through the same Base Theme in RTL', function () {
    $response = $this->withSession(['web_locale' => 'ar'])->get('_test/base-theme-page');

    $response->assertOk()
        ->assertSee('<html lang="ar" dir="rtl" data-theme="base">', false)
        ->assertSee('web-home__empty', false);

    expect(app(WebContextContract::class)->locale())->toBe('ar')
        ->and(app(WebContextContract::class)->direction())->toBe('rtl')
        ->and(app(WebContextContract::class)->activeTheme())->toBe('base');
});

it('renders every Web component under Base while preserving behavior contracts', function () {
    $response = $this->get('_test/base-theme-components');

    $response->assertOk()
        ->assertSee('web-button__label', false)
        ->assertSee('web-button--danger web-button--lg', false)
        ->assertSee('disabled', false)
        ->assertSee('web-card__header', false)
        ->assertSee('web-card__content', false)
        ->assertSee('web-card__footer', false)
        ->assertSee('web-badge--success', false)
        ->assertSee('role="alert"', false)
        ->assertSee('data-web-alert-dismiss', false)
        ->assertSee('aria-describedby="email-help email-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('data-web-accordion', false)
        ->assertSee('data-web-accordion-trigger', false)
        ->assertSee('data-web-accordion-panel', false)
        ->assertSee('aria-controls="base-item-panel"', false)
        ->assertSee('aria-labelledby="base-item-trigger"', false);
});

it('renders generic navigation safely and tolerates empty locations', function () {
    $navigation = app(NavigationRegistryContract::class);
    $navigation->register([
        'id' => 'generic-account',
        'title' => '<Generic account>',
        'url' => '/account?next=<unsafe>',
        'location' => 'header',
        'target' => '_blank',
    ]);

    $response = $this->get('_test/base-theme-page');

    $response->assertOk()
        ->assertSee('&lt;Generic account&gt;', false)
        ->assertSee('href="/account?next=&lt;unsafe&gt;"', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertDontSee('<Generic account>', false);
});

it('keeps Admin presentation outside the active Web theme', function () {
    $response = $this->get(route('admin.session.create'));

    $response->assertOk()->assertDontSee('data-theme="base"', false);
});

it('protects Admin mail notification and error namespaces from theme overrides', function () {
    $temp = sys_get_temp_dir().'/base_theme_protected_'.uniqid();
    $themePath = $temp.'/theme';
    $viewsPath = $themePath.'/views';
    mkdir($viewsPath, 0777, true);

    $finder = app('view.finder');
    expect($finder)->toBeInstanceOf(ThemeViewFinder::class);

    foreach (['admin', 'mail', 'notifications', 'errors'] as $namespace) {
        $originalPath = $temp.'/original/'.$namespace;
        $overridePath = $viewsPath.'/overrides/'.$namespace;
        mkdir($originalPath, 0777, true);
        mkdir($overridePath, 0777, true);
        file_put_contents($originalPath.'/proof.blade.php', "Original {$namespace}");
        file_put_contents($overridePath.'/proof.blade.php', "Hijacked {$namespace}");
        View::addNamespace($namespace, $originalPath);
    }

    $finder->setActiveThemeChain([new ThemeDefinition(
        id: 'protection-proof',
        name: 'Protection Proof',
        basePath: $themePath,
        viewsPath: $viewsPath,
    )]);

    foreach (['admin', 'mail', 'notifications', 'errors'] as $namespace) {
        expect(View::make("{$namespace}::proof")->render())
            ->toBe("Original {$namespace}")
            ->not->toContain('Hijacked');
    }

    (new Filesystem)->deleteDirectory($temp);
});

it('keeps Base source isolated and its override set intentionally small', function () {
    $basePath = base_path('themes/base');
    $files = collect((new Filesystem)->allFiles($basePath));
    $source = $files->map(fn ($file) => file_get_contents($file->getPathname()))->implode("\n");

    foreach (['Webkul\\Admin', 'Webkul\\Student', 'Webkul\\Event', 'Webkul\\LostAndFound', 'Webkul\\Shop', 'DB::', '::query(', 'Gate::', 'Bouncer::', 'admin.*', 'student.*', 'event.*', 'lost_found.*'] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }

    $componentOverrides = (new Filesystem)->allFiles($basePath.'/views/overrides/web/components');
    expect($componentOverrides)->toHaveCount(2)
        ->and(file_exists($basePath.'/views/overrides/web/layouts/master.blade.php'))->toBeTrue()
        ->and(file_exists($basePath.'/views/overrides/web/home/index.blade.php'))->toBeTrue();
});
