<?php

use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\Core\Exceptions\InvalidPackageComposition;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\User\Providers\UserServiceProvider;

uses(InteractsWithOptionalPackageComposition::class);

function sampleSyntheticPackageCatalog(): array
{
    return [
        'base_addon' => [
            'id' => 'base_addon',
            'composer_name' => 'laraseed/base-addon',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'requires' => [],
        ],
        'extended_addon' => [
            'id' => 'extended_addon',
            'composer_name' => 'laraseed/extended-addon',
            'provider' => UserServiceProvider::class,
            'concord_module' => null,
            'requires' => ['base_addon'],
        ],
    ];
}

it('keeps the repository development default Foundation only', function () {
    $source = file_get_contents(config_path('laraseed.php'));
    $example = file_get_contents(base_path('.env.example'));

    expect($source)->toContain("env('LARASEED_OPTIONAL_PACKAGES', '')")
        ->and($example)->toMatch('/^LARASEED_OPTIONAL_PACKAGES=$/m');
});

it('compiles canonical manifest metadata and resolves dependencies in topological order', function () {
    $catalog = sampleSyntheticPackageCatalog();
    $composition = new OptionalPackageComposition($catalog, ['extended_addon', 'base_addon']);

    expect(array_keys($catalog))->toBe(['base_addon', 'extended_addon'])
        ->and($composition->enabledPackages())->toBe(['base_addon', 'extended_addon'])
        ->and($composition->providers())->toBe([
            CoreServiceProvider::class,
            UserServiceProvider::class,
        ])
        ->and($composition->dependencyGraph())->toBe([
            'base_addon' => [],
            'extended_addon' => ['base_addon'],
        ]);
});

it('accepts every supported valid package set', function (array $enabled) {
    $catalog = sampleSyntheticPackageCatalog();
    $composition = new OptionalPackageComposition($catalog, $enabled);

    expect($composition->enabledPackages())->toHaveCount(count($enabled));
})->with([
    'Foundation only' => [[]],
    'Base addon' => [['base_addon']],
    'Base and Extended addons' => [['base_addon', 'extended_addon']],
]);

it('rejects invalid dependency combinations before application boot', function (array $enabled, string $message) {
    $catalog = sampleSyntheticPackageCatalog();

    expect(fn () => new OptionalPackageComposition($catalog, $enabled))
        ->toThrow(InvalidPackageComposition::class, $message);
})->with([
    'Extended without Base' => [['extended_addon'], 'Optional package "extended_addon" requires enabled package "base_addon".'],
]);

it('derives reverse dependency safety from the forward graph', function () {
    $catalog = sampleSyntheticPackageCatalog();
    $composition = new OptionalPackageComposition($catalog, ['base_addon', 'extended_addon']);

    expect($composition->enabledDependents('base_addon'))->toBe(['extended_addon'])
        ->and($composition->canDisable('base_addon'))->toBeFalse()
        ->and($composition->canDisable('extended_addon'))->toBeTrue();
});

it('rejects unknown configured package IDs', function () {
    $catalog = sampleSyntheticPackageCatalog();

    expect(fn () => new OptionalPackageComposition($catalog, ['base_addon', 'unknown_pkg']))
        ->toThrow(InvalidPackageComposition::class, 'Unknown Optional package ID [unknown_pkg].');
});

it('rejects duplicate package IDs from manifest discovery', function () {
    $manifest = [
        'name' => 'laraseed/sample-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'sample_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path, $path]))
            ->toThrow(InvalidPackageComposition::class, 'Duplicate Optional package ID [sample_pkg].');
    } finally {
        @unlink($path);
    }
});

it('rejects invalid provider class in manifest', function () {
    $manifest = [
        'name' => 'laraseed/sample-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'sample_pkg',
                'type' => 'optional',
                'provider' => 'NonExistent\\Class\\Provider',
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, 'Optional package [sample_pkg] declares an invalid provider class.');
    } finally {
        @unlink($path);
    }
});

it('rejects dependency cycles deterministically', function () {
    $catalog = sampleSyntheticPackageCatalog();
    $catalog['base_addon']['requires'] = ['extended_addon'];

    expect(fn () => new OptionalPackageComposition($catalog, ['base_addon', 'extended_addon']))
        ->toThrow(InvalidPackageComposition::class, 'Optional package dependency cycle detected');
});

it('fails invalid deployment compositions with the architecture exception', function (array $enabled, string $message) {
    $result = $this->runCompositionCommand($enabled, ['about', '--only=environment']);

    expect($result['exit_code'])->not->toBe(0)
        ->and($result['output'].$result['error'])->toContain($message)
        ->not->toContain('Class not found')
        ->not->toContain('SQLSTATE');
})->with([
    'unknown package' => [['unknown_optional_package'], 'Unknown Optional package ID [unknown_optional_package].'],
]);

it('reports installed and enabled optional package status accurately via laraseed:packages without mutating state', function () {
    $envBefore = file_exists(base_path('.env')) ? file_get_contents(base_path('.env')) : null;

    $foundationResult = $this->runCompositionCommand([], ['laraseed:packages']);
    expect($foundationResult['exit_code'])->toBe(0)
        ->and($foundationResult['output'])->toContain('Active optional composition: Foundation only.')
        ->and($foundationResult['output'])->toContain('Optional package composition is controlled through LARASEED_OPTIONAL_PACKAGES.');

    $envAfter = file_exists(base_path('.env')) ? file_get_contents(base_path('.env')) : null;
    expect($envAfter)->toBe($envBefore);
});

it('verifies that Foundation-only route composition contains zero deleted package routes', function () {
    $routes = $this->routesForComposition([]);
    $names = array_filter(array_column($routes, 'name'));
    $actions = array_column($routes, 'action');

    expect($routes)->not->toBeEmpty();
    foreach ($names as $name) {
        expect($name)->not->toStartWith('student.')
            ->not->toStartWith('website.')
            ->not->toBe('web.home');
    }
    foreach ($actions as $action) {
        expect($action)->not->toContain('Webkul\\Student')
            ->not->toContain('Webkul\\LostAndFound')
            ->not->toContain('Webkul\\Website')
            ->not->toContain('Webkul\\Web');
    }
});
