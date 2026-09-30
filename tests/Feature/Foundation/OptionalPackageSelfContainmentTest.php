<?php

use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\Core\Exceptions\InvalidPackageComposition;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

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

it('enforces that Foundation packages have zero references to optional packages', function () {
    $foundationPackages = ['Core', 'Admin', 'User', 'DataGrid', 'Installer', 'Web', 'Theme'];
    $forbidden = [
        'Webkul\\Student',
        'Webkul\\LostAndFound',
        'Webkul\\Website',
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

it('enforces that Student package has zero reverse dependencies on LostAndFound', function () {
    $studentRoot = base_path('packages/Webkul/Student');
    $forbidden = [
        'Webkul\\LostAndFound',
        'student.lost_found',
        'Webkul\\Website',
    ];

    $violations = scanDirectoryForForbiddenPatterns($studentRoot, $forbidden);

    expect($violations)->toBe([]);
});

it('enforces that central root tests contain zero package business behavior tests', function () {
    $rootFeature = base_path('tests/Feature');
    $rootUnit = base_path('tests/Unit');
    $forbidden = [
        'Webkul\\Student\\',
        'Webkul\\LostAndFound\\',
        'Webkul\\Website\\',
    ];

    // Exclude composition and decoupling architecture test fixtures
    $excluded = ['tests/Feature/Foundation/'];

    $violations = array_merge(
        scanDirectoryForForbiddenPatterns($rootFeature, $forbidden, $excluded),
        scanDirectoryForForbiddenPatterns($rootUnit, $forbidden),
    );

    expect($violations)->toBe([]);
});

it('enforces package-local test discovery and physical existence', function () {
    $studentTests = glob(base_path('packages/Webkul/Student/tests/Feature/*Test.php'));
    $lfFeatureTests = glob(base_path('packages/Webkul/LostAndFound/tests/Feature/*Test.php'));
    $lfUnitTests = glob(base_path('packages/Webkul/LostAndFound/tests/Unit/*Test.php'));
    $websiteTests = glob(base_path('packages/Webkul/Website/tests/Feature/*Test.php'));

    expect($studentTests)->toHaveCount(5)
        ->and($lfFeatureTests)->toHaveCount(19)
        ->and($lfUnitTests)->toHaveCount(5)
        ->and($websiteTests)->toHaveCount(5);

    $phpunitXml = file_get_contents(base_path('phpunit.xml'));

    expect($phpunitXml)->toContain('testsuite name="Student"')
        ->and($phpunitXml)->toContain('testsuite name="LostAndFound"')
        ->and($phpunitXml)->toContain('testsuite name="Website"');
});

it('enforces that LostAndFound has zero reverse dependencies on Website', function () {
    $lostAndFoundRoot = base_path('packages/Webkul/LostAndFound');
    $forbidden = [
        'Webkul\\Website',
        'website::',
        'website_',
    ];

    $violations = scanDirectoryForForbiddenPatterns($lostAndFoundRoot, $forbidden);

    expect($violations)->toBe([]);
});

it('enforces that Website never imports LostAndFound models or repositories and knows zero tables', function () {
    $websiteSrc = base_path('packages/Webkul/Website/src');
    $forbidden = [
        'Webkul\\LostAndFound\\Models',
        'Webkul\\LostAndFound\\Repositories',
        'lost_found_items',
        'lost_found_reports',
        'lost_found_claims',
        'lost_found_item_images',
        'lost_found_report_images',
        'lost_found_custody_records',
        'lost_found_handovers',
        'lost_found_categories',
    ];

    $violations = scanDirectoryForForbiddenPatterns($websiteSrc, $forbidden);

    expect($violations)->toBe([]);
});

it('enforces that migrations, routes, ACL, and menu are strictly package-owned', function () {
    // Migrations
    $rootMigrations = glob(base_path('database/migrations/*.php'));
    foreach ($rootMigrations as $migration) {
        $content = file_get_contents($migration);
        expect($content)->not->toContain('students', 'found_items', 'lost_reports', 'custody_records');
    }

    // Student ownership
    expect(file_exists(base_path('packages/Webkul/Student/src/Routes/admin-routes.php')))->toBeTrue()
        ->and(file_exists(base_path('packages/Webkul/Student/src/Config/acl.php')))->toBeTrue()
        ->and(file_exists(base_path('packages/Webkul/Student/src/Config/menu.php')))->toBeTrue();

    // LostAndFound ownership
    expect(file_exists(base_path('packages/Webkul/LostAndFound/src/Routes/employee-routes.php')))->toBeTrue()
        ->and(file_exists(base_path('packages/Webkul/LostAndFound/src/Routes/student-routes.php')))->toBeTrue()
        ->and(file_exists(base_path('packages/Webkul/LostAndFound/src/Config/acl.php')))->toBeTrue();
});

it('certifies the full multi-composition matrix execution', function () {
    $catalog = (new OptionalPackageManifestLoader)->load([
        base_path('packages/Webkul/Student/composer.json'),
        base_path('packages/Webkul/LostAndFound/composer.json'),
        base_path('packages/Webkul/Website/composer.json'),
    ]);

    // Matrix A: Foundation only
    $compA = new OptionalPackageComposition($catalog, []);
    expect($compA->enabledPackages())->toBe([])
        ->and($compA->providers())->toBe([]);

    // Matrix B: Student only
    $compB = new OptionalPackageComposition($catalog, ['student']);
    expect($compB->enabledPackages())->toBe(['student'])
        ->and($compB->providers())->toBe([Webkul\Student\Providers\StudentServiceProvider::class]);

    // Matrix C: Student + LostAndFound (topological order)
    $compC = new OptionalPackageComposition($catalog, ['lost_and_found', 'student']);
    expect($compC->enabledPackages())->toBe(['student', 'lost_and_found'])
        ->and($compC->providers())->toBe([
            Webkul\Student\Providers\StudentServiceProvider::class,
            Webkul\LostAndFound\Providers\LostAndFoundServiceProvider::class,
        ]);

    // Matrix D: LostAndFound only (must throw)
    expect(fn () => new OptionalPackageComposition($catalog, ['lost_and_found']))
        ->toThrow(InvalidPackageComposition::class, 'Optional package "lost_and_found" requires enabled package "student".');

    // Matrix E: Website only
    $compE = new OptionalPackageComposition($catalog, ['website']);
    expect($compE->enabledPackages())->toBe(['website'])
        ->and($compE->providers())->toBe([Webkul\Website\Providers\WebsiteServiceProvider::class]);
});

it('verifies synchronization between central catalog, root composer, and phpunit testsuites', function () {
    $catalogPaths = [
        base_path('packages/Webkul/Student/composer.json'),
        base_path('packages/Webkul/LostAndFound/composer.json'),
        base_path('packages/Webkul/Website/composer.json'),
    ];

    $loader = new OptionalPackageManifestLoader;
    $packages = $loader->load($catalogPaths);

    $rootComposer = json_decode(file_get_contents(base_path('composer.json')), true);
    $rootPsr4 = $rootComposer['autoload']['psr-4'] ?? [];
    $phpunitXml = file_get_contents(base_path('phpunit.xml'));

    foreach ($catalogPaths as $manifestPath) {
        expect(file_exists($manifestPath))->toBeTrue();
    }

    foreach ($packages as $id => $pkg) {
        expect($pkg['id'])->toBe($id)
            ->and($pkg['composer_name'])->not->toBeEmpty();

        if ($pkg['provider']) {
            expect(class_exists($pkg['provider']))->toBeTrue();
        }

        $manifestPath = match ($id) {
            'student' => base_path('packages/Webkul/Student/composer.json'),
            'lost_and_found' => base_path('packages/Webkul/LostAndFound/composer.json'),
            'website' => base_path('packages/Webkul/Website/composer.json'),
        };

        $packageComposer = json_decode(file_get_contents($manifestPath), true);
        $packagePsr4 = $packageComposer['autoload']['psr-4'] ?? [];
        foreach ($packagePsr4 as $namespace => $dir) {
            expect($rootPsr4)->toHaveKey($namespace);
        }

        $expectedSuiteName = match ($id) {
            'student' => 'Student',
            'lost_and_found' => 'LostAndFound',
            'website' => 'Website',
            default => null,
        };
        if ($expectedSuiteName) {
            expect($phpunitXml)->toContain('testsuite name="'.$expectedSuiteName.'"');
        }
    }
});

it('verifies that root route / falls back cleanly to default Web homepage when Website is absent', function () {
    $routes = $this->routesForComposition([]);
    $root = collect($routes)->firstWhere('uri', '/');

    expect($root)->not->toBeNull()
        ->and($root['name'])->toBe('web.home')
        ->and($root['action'])->toBe('Webkul\Web\Http\Controllers\HomeController@index');
});



