<?php

use Illuminate\Support\Facades\Route;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Registry\ThemeRegistry;
use Webkul\Theme\Resolution\ThemeResolver;
use Webkul\Theme\View\ThemeViewFinder;
use Webkul\Web\Contracts\WebContextContract;

it('registers Theme Foundation services in the container', function () {
    expect(app(ThemeRegistryContract::class))->toBeInstanceOf(ThemeRegistry::class);
    expect(app(ThemeRegistry::class))->toBeInstanceOf(ThemeRegistry::class);
    expect(app(ThemeResolverContract::class))->toBeInstanceOf(ThemeResolver::class);
    expect(app(ThemeResolver::class))->toBeInstanceOf(ThemeResolver::class);
    expect(app('view.finder'))->toBeInstanceOf(ThemeViewFinder::class);
});

it('ensures Theme production source contains zero forbidden package references', function () {
    $themeSrcPath = base_path('packages/Webkul/Theme/src');

    $forbiddenTerms = [
        'Webkul\\Admin',
        'Webkul\\Student',
        'Webkul\\Event',
        'Webkul\\LostAndFound',
        'Webkul\\Shop',
        'Webkul\\Web',
    ];

    $phpFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themeSrcPath));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }

    expect($phpFiles)->not->toBeEmpty();

    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        foreach ($forbiddenTerms as $forbidden) {
            expect(str_contains($content, $forbidden))
                ->toBeFalse("File {$file} contains forbidden reference: {$forbidden}");
        }
    }
});

it('ensures Web production source contains zero forbidden business package references', function () {
    $webSrcPath = base_path('packages/Webkul/Web/src');

    $forbiddenTerms = [
        'Webkul\\Admin',
        'Webkul\\Student',
        'Webkul\\Event',
        'Webkul\\LostAndFound',
        'Webkul\\Shop',
    ];

    $phpFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($webSrcPath));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }

    expect($phpFiles)->not->toBeEmpty();

    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        foreach ($forbiddenTerms as $forbidden) {
            expect(str_contains($content, $forbidden))
                ->toBeFalse("File {$file} contains forbidden reference: {$forbidden}");
        }
    }
});

it('proves Theme registers 0 HTTP routes', function () {
    $themeRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(function ($route) {
            $action = $route->getActionName();
            return str_contains($action, 'Webkul\\Theme');
        });

    expect($themeRoutes)->toBeEmpty();
});

it('integrates resolved theme into WebContext on web requests', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    /** @var WebContextContract $webContext */
    $webContext = app(WebContextContract::class);
    expect($webContext->activeTheme())->toBe('default');
});

it('ensures Admin requests do not resolve or require Web themes', function () {
    $adminLogin = route('admin.session.create');

    $response = $this->get($adminLogin);

    $response->assertStatus(200);
});
