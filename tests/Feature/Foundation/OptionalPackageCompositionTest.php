<?php

use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\Core\Exceptions\InvalidPackageComposition;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;
use Webkul\LostAndFound\Providers\LostAndFoundServiceProvider;
use Webkul\Student\Providers\StudentServiceProvider;

uses(InteractsWithOptionalPackageComposition::class);

function optionalPackageManifestPaths(): array
{
    return [
        base_path('packages/Webkul/Student/composer.json'),
        base_path('packages/Webkul/LostAndFound/composer.json'),
    ];
}

it('keeps the repository development default Foundation only', function () {
    $source = file_get_contents(config_path('campushub.php'));
    $example = file_get_contents(base_path('.env.example'));

    expect($source)->toContain("env('CAMPUSHUB_OPTIONAL_PACKAGES', '')")
        ->and($example)->toMatch('/^CAMPUSHUB_OPTIONAL_PACKAGES=$/m');
});

it('compiles canonical manifest metadata and Composer dependencies', function () {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());
    $composition = new OptionalPackageComposition($catalog, ['lost_and_found', 'student']);

    expect(array_keys($catalog))->toBe(['lost_and_found', 'student'])
        ->and($composition->enabledPackages())->toBe(['student', 'lost_and_found'])
        ->and($composition->providers())->toBe([
            StudentServiceProvider::class,
            LostAndFoundServiceProvider::class,
        ])
        ->and($composition->dependencyGraph())->toBe([
            'lost_and_found' => ['student'],
            'student' => [],
        ])
        ->and($composition->concordModules())->toBe([
            Webkul\LostAndFound\Providers\ModuleServiceProvider::class,
        ]);
});

it('accepts every supported valid package set', function (array $enabled) {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());
    $composition = new OptionalPackageComposition($catalog, $enabled);

    expect($composition->enabledPackages())->toHaveCount(count($enabled));
})->with([
    'Foundation only' => [[]],
    'Student' => [['student']],
    'Student and LostAndFound' => [['student', 'lost_and_found']],
]);

it('rejects invalid dependency combinations before application boot', function (array $enabled, string $message) {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());

    expect(fn () => new OptionalPackageComposition($catalog, $enabled))
        ->toThrow(InvalidPackageComposition::class, $message);
})->with([
    'LostAndFound without Student' => [['lost_and_found'], 'Optional package "lost_and_found" requires enabled package "student".'],
]);

it('derives reverse dependency safety from the forward graph', function () {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());
    $composition = new OptionalPackageComposition($catalog, ['student', 'lost_and_found']);

    expect($composition->enabledDependents('student'))->toBe(['lost_and_found'])
        ->and($composition->canDisable('student'))->toBeFalse()
        ->and($composition->canDisable('lost_and_found'))->toBeTrue();
});

it('rejects unknown configured package IDs', function () {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());

    expect(fn () => new OptionalPackageComposition($catalog, ['student', 'studnet']))
        ->toThrow(InvalidPackageComposition::class, 'Unknown Optional package ID [studnet].');
});

it('rejects duplicate package IDs from manifest discovery', function () {
    expect(fn () => (new OptionalPackageManifestLoader)->load([
        optionalPackageManifestPaths()[0],
        optionalPackageManifestPaths()[0],
    ]))->toThrow(InvalidPackageComposition::class, 'Duplicate Optional package ID [student].');
});

it('rejects invalid provider and Concord module classes', function (string $field) {
    $manifest = json_decode(file_get_contents(optionalPackageManifestPaths()[0]), true, flags: JSON_THROW_ON_ERROR);
    $manifest['extra']['campushub'][$field] = 'CampusHub\\Missing\\Provider';
    $path = tempnam(sys_get_temp_dir(), 'campushub-package-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class);
    } finally {
        unlink($path);
    }
})->with(['provider', 'concord_module']);

it('rejects dependency cycles deterministically', function () {
    $catalog = (new OptionalPackageManifestLoader)->load(optionalPackageManifestPaths());
    $catalog['student']['requires'] = ['lost_and_found'];

    expect(fn () => new OptionalPackageComposition($catalog, ['student', 'lost_and_found']))
        ->toThrow(InvalidPackageComposition::class, 'Optional package dependency cycle detected');
});

it('uses one state for provider and Concord route composition across the valid matrix', function (array $enabled, int $count, bool $student, bool $lostFound) {
    $routes = $this->routesForComposition($enabled);
    $names = array_column($routes, 'name');

    expect($routes)->toHaveCount($count)
        ->and(in_array('student.login', $names, true))->toBe($student)
        ->and(in_array('student.lost_found.reports.store', $names, true))->toBe($lostFound)
        ->and(in_array('web.home', $names, true))->toBeTrue();
})->with([
    'Foundation only' => [[], 69, false, false],
    'Student' => [['student'], 81, true, false],
    'Student and LostAndFound' => [['student', 'lost_and_found'], 102, true, true],
]);

it('fails invalid deployment compositions with the architecture exception', function (array $enabled, string $message) {
    $result = $this->runCompositionCommand($enabled, ['about', '--only=environment']);

    expect($result['exit_code'])->not->toBe(0)
        ->and($result['output'].$result['error'])->toContain($message)
        ->not->toContain('Class not found')
        ->not->toContain('SQLSTATE');
})->with([
    'LostAndFound without Student' => [['lost_and_found'], 'Optional package "lost_and_found" requires enabled package "student".'],
    'unknown package' => [['student', 'studnet'], 'Unknown Optional package ID [studnet].'],
]);

it('reports installed and enabled optional package status accurately via campushub:packages without mutating state', function () {
    $envBefore = file_exists(base_path('.env')) ? file_get_contents(base_path('.env')) : null;

    // 1. Foundation-only composition
    $foundationResult = $this->runCompositionCommand([], ['campushub:packages']);
    expect($foundationResult['exit_code'])->toBe(0)
        ->and($foundationResult['output'])->toContain('Student')
        ->and($foundationResult['output'])->toContain('LostAndFound')
        ->and($foundationResult['output'])->toContain('Website')
        ->and($foundationResult['output'])->toContain('DISABLED')
        ->and($foundationResult['output'])->toContain('Active optional composition: Foundation only.')
        ->and($foundationResult['output'])->toContain('CAMPUSHUB_OPTIONAL_PACKAGES');

    // 2. Website-only composition
    $websiteResult = $this->runCompositionCommand(['website'], ['campushub:packages']);
    expect($websiteResult['exit_code'])->toBe(0)
        ->and($websiteResult['output'])->toContain('ACTIVE')
        ->and($websiteResult['output'])->toContain('DISABLED')
        ->and($websiteResult['output'])->toContain('Active optional composition: website');

    // 3. Full composition
    $fullResult = $this->runCompositionCommand(['student', 'lost_and_found', 'website'], ['campushub:packages']);
    expect($fullResult['exit_code'])->toBe(0)
        ->and($fullResult['output'])->toContain('registered')
        ->and($fullResult['output'])->toContain('ACTIVE')
        ->and($fullResult['output'])->not->toContain('DISABLED')
        ->and($fullResult['output'])->toContain('Active optional composition: student, lost_and_found, website');

    $envAfter = file_exists(base_path('.env')) ? file_get_contents(base_path('.env')) : null;
    expect($envAfter)->toBe($envBefore);
});

