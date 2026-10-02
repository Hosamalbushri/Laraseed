<?php

use Tests\Support\InteractsWithOptionalPackageComposition;

uses(InteractsWithOptionalPackageComposition::class);

function scanDirectoryForForbiddenPatterns(string $directory, array $forbidden, array $excludePaths = []): array
{
    $violations = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $pathname = $file->getPathname();

        foreach ($excludePaths as $exclude) {
            if (str_contains($pathname, $exclude)) {
                continue 2;
            }
        }

        $contents = file_get_contents($pathname);

        foreach ($forbidden as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $pathname.': contains "'.$pattern.'"';
            }
        }
    }

    return $violations;
}

it('enforces that Foundation packages have zero references to optional or deleted packages', function () {
    $foundationPackages = ['Core', 'Admin', 'User', 'DataGrid', 'Installer'];
    $forbidden = [
        'Webkul\\Student',
        'Webkul\\LostAndFound',
        'Webkul\\Website',
        'Webkul\\Web',
        'Webkul\\Theme',
    ];

    $violations = [];

    foreach ($foundationPackages as $pkg) {
        $path = base_path("packages/Webkul/{$pkg}/src");
        if (is_dir($path)) {
            $violations = array_merge(
                $violations,
                scanDirectoryForForbiddenPatterns($path, $forbidden),
            );
        }
    }

    expect($violations)->toBe([]);
});

it('enforces that central root tests contain zero package business behavior tests or deleted package references', function () {
    $rootFeature = base_path('tests/Feature');
    $rootUnit = base_path('tests/Unit');
    $forbidden = [
        'Webkul\\Student\\',
        'Webkul\\LostAndFound\\',
        'Webkul\\Website\\',
        'Webkul\\Web\\',
        'Webkul\\Theme\\',
    ];

    // Exclude composition architecture test fixtures
    $excluded = ['tests/Feature/Foundation/'];

    $violations = array_merge(
        scanDirectoryForForbiddenPatterns($rootFeature, $forbidden, $excluded),
        scanDirectoryForForbiddenPatterns($rootUnit, $forbidden),
    );

    expect($violations)->toBe([]);
});

it('enforces that root migrations contain zero tables from deleted packages', function () {
    $rootMigrations = glob(base_path('database/migrations/*.php'));
    $forbiddenTables = ['students', 'found_items', 'lost_reports', 'custody_records', 'lost_found_items'];

    foreach ($rootMigrations as $migration) {
        $content = file_get_contents($migration);
        foreach ($forbiddenTables as $table) {
            expect($content)->not->toContain($table);
        }
    }
});

it('enforces that deleted package directories and phpunit testsuites are absent', function () {
    $deletedPackages = ['Student', 'LostAndFound', 'Website', 'Web', 'Theme'];

    foreach ($deletedPackages as $pkg) {
        expect(is_dir(base_path("packages/Webkul/{$pkg}")))->toBeFalse();
    }

    $phpunitXml = file_get_contents(base_path('phpunit.xml'));

    expect($phpunitXml)->not->toContain('testsuite name="Student"')
        ->not->toContain('testsuite name="LostAndFound"')
        ->not->toContain('testsuite name="Website"')
        ->not->toContain('testsuite name="Web"');
});

it('enforces that central configuration and providers contain zero residue of deleted packages', function () {
    $laraseedConfig = file_get_contents(config_path('laraseed.php'));
    expect($laraseedConfig)->not->toContain('Student/composer.json')
        ->not->toContain('LostAndFound/composer.json')
        ->not->toContain('Website/composer.json');

    $providersFile = file_get_contents(base_path('bootstrap/providers.php'));
    expect($providersFile)->not->toContain('Webkul\\Web\\Providers\\WebServiceProvider')
        ->not->toContain('Webkul\\Student\\Providers\\StudentServiceProvider')
        ->not->toContain('Webkul\\LostAndFound\\Providers\\LostAndFoundServiceProvider')
        ->not->toContain('Webkul\\Website\\Providers\\WebsiteServiceProvider');
});

it('enforces that Foundation route composition contains zero routes from deleted packages', function () {
    $routes = $this->routesForComposition([]);
    $root = collect($routes)->firstWhere('uri', '/');

    if ($root !== null) {
        expect($root['name'] ?? '')->toBe('laraseed.web.entry');
        expect($root['action'] ?? '')->toBe('App\\Http\\Controllers\\WebEntryPointController@index');
    }

    foreach ($routes as $route) {
        $action = $route['action'] ?? '';
        expect($action)->not->toContain('Webkul\\Web')
            ->not->toContain('Webkul\\Student')
            ->not->toContain('Webkul\\LostAndFound')
            ->not->toContain('Webkul\\Website');
    }
});
