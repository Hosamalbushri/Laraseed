<?php

use Illuminate\Support\Facades\View;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\View\ThemeViewFinder;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/test_views_' . uniqid();
    mkdir($this->tempDir . '/pkg_views', 0777, true);
    mkdir($this->tempDir . '/base_theme/views/overrides/testpkg', 0777, true);
    mkdir($this->tempDir . '/child_theme/views/overrides/testpkg', 0777, true);

    // Register package namespace
    View::addNamespace('testpkg', $this->tempDir . '/pkg_views');
});

afterEach(function () {
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        exec('rm -rf ' . escapeshellarg($this->tempDir));
    }
});

it('resolves package default view when neither child nor parent overrides it', function () {
    file_put_contents($this->tempDir . '/pkg_views/sample.blade.php', '<div>Package Default</div>');

    $viewFinder = app('view.finder');
    expect($viewFinder)->toBeInstanceOf(ThemeViewFinder::class);

    $viewFinder->setActiveThemeChain([]);

    $content = View::make('testpkg::sample')->render();
    expect(trim($content))->toBe('<div>Package Default</div>');
});

it('resolves base theme override when child theme does not provide an override', function () {
    file_put_contents($this->tempDir . '/pkg_views/sample.blade.php', '<div>Package Default</div>');
    file_put_contents($this->tempDir . '/base_theme/views/overrides/testpkg/sample.blade.php', '<div>Base Theme Override</div>');

    $baseTheme = new ThemeDefinition(
        id: 'base-theme',
        name: 'Base Theme',
        basePath: $this->tempDir . '/base_theme',
        viewsPath: $this->tempDir . '/base_theme/views'
    );

    $childTheme = new ThemeDefinition(
        id: 'child-theme',
        name: 'Child Theme',
        basePath: $this->tempDir . '/child_theme',
        parent: 'base-theme',
        viewsPath: $this->tempDir . '/child_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$childTheme, $baseTheme]);

    $content = View::make('testpkg::sample')->render();
    expect(trim($content))->toBe('<div>Base Theme Override</div>');
});

it('resolves child theme override when child theme provides an override', function () {
    file_put_contents($this->tempDir . '/pkg_views/sample.blade.php', '<div>Package Default</div>');
    file_put_contents($this->tempDir . '/base_theme/views/overrides/testpkg/sample.blade.php', '<div>Base Theme Override</div>');
    file_put_contents($this->tempDir . '/child_theme/views/overrides/testpkg/sample.blade.php', '<div>Child Theme Override</div>');

    $baseTheme = new ThemeDefinition(
        id: 'base-theme',
        name: 'Base Theme',
        basePath: $this->tempDir . '/base_theme',
        viewsPath: $this->tempDir . '/base_theme/views'
    );

    $childTheme = new ThemeDefinition(
        id: 'child-theme',
        name: 'Child Theme',
        basePath: $this->tempDir . '/child_theme',
        parent: 'base-theme',
        viewsPath: $this->tempDir . '/child_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$childTheme, $baseTheme]);

    $content = View::make('testpkg::sample')->render();
    expect(trim($content))->toBe('<div>Child Theme Override</div>');
});

it('resolves Web Foundation view overrides when present in active theme', function () {
    mkdir($this->tempDir . '/active_theme/views/overrides/web', 0777, true);
    file_put_contents($this->tempDir . '/active_theme/views/overrides/web/home-test.blade.php', '<h1>Themed Web Home</h1>');

    $theme = new ThemeDefinition(
        id: 'active-theme',
        name: 'Active Theme',
        basePath: $this->tempDir . '/active_theme',
        viewsPath: $this->tempDir . '/active_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$theme]);

    $content = View::make('web::home-test')->render();
    expect(trim($content))->toBe('<h1>Themed Web Home</h1>');
});

it('throws InvalidArgumentException when view does not exist in theme or package', function () {
    $theme = new ThemeDefinition(
        id: 'empty-theme',
        name: 'Empty Theme',
        basePath: $this->tempDir . '/base_theme',
        viewsPath: $this->tempDir . '/base_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$theme]);

    expect(fn () => View::make('testpkg::completely_non_existent_view')->render())
        ->toThrow(InvalidArgumentException::class);
});

it('guarantees Admin immunity by preventing themes from overriding admin views', function () {
    mkdir($this->tempDir . '/hostile_theme/views/overrides/admin', 0777, true);
    file_put_contents($this->tempDir . '/hostile_theme/views/overrides/admin/hijack.blade.php', '<h1>Hijacked Admin View</h1>');

    // Register test admin view in admin namespace
    mkdir($this->tempDir . '/admin_views', 0777, true);
    file_put_contents($this->tempDir . '/admin_views/hijack.blade.php', '<h1>Genuine Admin View</h1>');
    View::addNamespace('admin', $this->tempDir . '/admin_views');

    $hostileTheme = new ThemeDefinition(
        id: 'hostile-theme',
        name: 'Hostile Theme',
        basePath: $this->tempDir . '/hostile_theme',
        viewsPath: $this->tempDir . '/hostile_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$hostileTheme]);

    $content = View::make('admin::hijack')->render();
    expect(trim($content))->toBe('<h1>Genuine Admin View</h1>')
        ->not->toContain('Hijacked');
});

it('resolves Web component overrides through active theme hierarchy', function () {
    mkdir($this->tempDir . '/custom_theme/views/overrides/web/components', 0777, true);
    file_put_contents(
        $this->tempDir . '/custom_theme/views/overrides/web/components/button.blade.php',
        '<button class="themed-custom-btn">{{ $slot }}</button>'
    );

    $customTheme = new ThemeDefinition(
        id: 'custom-theme',
        name: 'Custom Theme',
        basePath: $this->tempDir . '/custom_theme',
        viewsPath: $this->tempDir . '/custom_theme/views'
    );

    /** @var ThemeViewFinder $viewFinder */
    $viewFinder = app('view.finder');
    $viewFinder->setActiveThemeChain([$customTheme]);

    $content = View::make('web::components.button', ['slot' => 'Themed Button'])->render();
    expect(trim($content))->toBe('<button class="themed-custom-btn">Themed Button</button>');
});
