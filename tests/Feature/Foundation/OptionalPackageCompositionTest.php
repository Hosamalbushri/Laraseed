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

it('loads V1 manifests without capabilities as an empty capabilities array', function () {
    $manifest = [
        'name' => 'laraseed/legacy-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'legacy_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        $catalog = (new OptionalPackageManifestLoader)->load([$path]);
        expect($catalog['legacy_pkg']['capabilities'])->toBe([]);
    } finally {
        @unlink($path);
    }
});

it('loads manifests with valid empty, single, and multiple capabilities', function () {
    $manifest = [
        'name' => 'laraseed/multi-cap-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'multi_cap_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => [
                    'admin' => [
                        'provider' => UserServiceProvider::class,
                        'enabled' => true,
                    ],
                    'api' => [
                        'provider' => CoreServiceProvider::class,
                        'enabled' => false,
                    ],
                    'reporting_v2' => [
                        'provider' => UserServiceProvider::class,
                    ],
                ],
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        $catalog = (new OptionalPackageManifestLoader)->load([$path]);
        expect($catalog['multi_cap_pkg']['capabilities'])->toBe([
            'admin' => [
                'provider' => UserServiceProvider::class,
                'enabled' => true,
            ],
            'api' => [
                'provider' => CoreServiceProvider::class,
                'enabled' => false,
            ],
            'reporting_v2' => [
                'provider' => UserServiceProvider::class,
                'enabled' => true,
            ],
        ]);
    } finally {
        @unlink($path);
    }
});

it('rejects invalid capability container formats', function (mixed $invalidCapabilities) {
    $manifest = [
        'name' => 'laraseed/invalid-container-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'invalid_container_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => $invalidCapabilities,
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, 'Optional package [invalid_container_pkg] declares an invalid capabilities definition.');
    } finally {
        @unlink($path);
    }
})->with([
    'string' => ['invalid_string'],
    'integer' => [12345],
    'boolean' => [true],
]);

it('rejects invalid capability names', function (string $invalidName) {
    $manifest = [
        'name' => 'laraseed/invalid-name-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'invalid_name_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => [
                    $invalidName => [
                        'provider' => UserServiceProvider::class,
                    ],
                ],
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, "Optional package [invalid_name_pkg] declares an invalid capability name [{$invalidName}].");
    } finally {
        @unlink($path);
    }
})->with([
    'uppercase' => ['Admin'],
    'kebab-case' => ['admin-ui'],
    'path traversal' => ['../admin'],
    'slash separator' => ['admin/provider'],
    'leading underscore' => ['_admin'],
    'leading digit' => ['1admin'],
    'dot notation' => ['admin.ui'],
]);

it('rejects malformed capability definitions', function (mixed $definition, string $expectedMessage) {
    $manifest = [
        'name' => 'laraseed/malformed-def-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'malformed_def_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => [
                    'admin' => $definition,
                ],
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, $expectedMessage);
    } finally {
        @unlink($path);
    }
})->with([
    'non-array definition' => ['just_a_string', 'Optional package [malformed_def_pkg] capability [admin] must be an object definition.'],
    'missing provider' => [['enabled' => true], 'Optional package [malformed_def_pkg] capability [admin] declares an invalid provider class.'],
    'provider not string' => [['provider' => 12345], 'Optional package [malformed_def_pkg] capability [admin] declares an invalid provider class.'],
    'empty provider string' => [['provider' => ''], 'Optional package [malformed_def_pkg] capability [admin] declares an invalid provider class.'],
    'non-existent class' => [['provider' => 'NonExistent\\Admin\\Provider'], 'Optional package [malformed_def_pkg] capability [admin] declares an invalid provider class.'],
    'non-ServiceProvider class' => [['provider' => stdClass::class], 'Optional package [malformed_def_pkg] capability [admin] declares an invalid provider class.'],
]);

it('rejects unknown fields in capability definitions', function () {
    $manifest = [
        'name' => 'laraseed/unknown-field-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'unknown_field_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => [
                    'admin' => [
                        'provider' => UserServiceProvider::class,
                        'enabled' => true,
                        'middleware' => ['web', 'admin'],
                    ],
                ],
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, 'Optional package [unknown_field_pkg] capability [admin] contains unknown field [middleware].');
    } finally {
        @unlink($path);
    }
});

it('rejects non-boolean enabled flags in capability definitions', function () {
    $manifest = [
        'name' => 'laraseed/invalid-enabled-pkg',
        'extra' => [
            'laraseed' => [
                'id' => 'invalid_enabled_pkg',
                'type' => 'optional',
                'provider' => CoreServiceProvider::class,
                'capabilities' => [
                    'admin' => [
                        'provider' => UserServiceProvider::class,
                        'enabled' => 'yes',
                    ],
                ],
            ],
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'laraseed-test-manifest-');
    file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new OptionalPackageManifestLoader)->load([$path]))
            ->toThrow(InvalidPackageComposition::class, 'Optional package [invalid_enabled_pkg] capability [admin] declares an invalid enabled flag.');
    } finally {
        @unlink($path);
    }
});

it('inspects package capabilities via hasCapability, capability, and capabilities', function () {
    $catalog = [
        'blog' => [
            'id' => 'blog',
            'composer_name' => 'laraseed/blog',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
                'api' => [
                    'provider' => CoreServiceProvider::class,
                    'enabled' => false,
                ],
            ],
            'requires' => [],
        ],
    ];

    $composition = new OptionalPackageComposition($catalog, ['blog']);

    expect($composition->hasCapability('blog', 'admin'))->toBeTrue()
        ->and($composition->hasCapability('blog', 'api'))->toBeTrue()
        ->and($composition->hasCapability('blog', 'cli'))->toBeFalse()
        ->and($composition->capability('blog', 'admin'))->toBe([
            'provider' => UserServiceProvider::class,
            'enabled' => true,
        ])
        ->and($composition->capability('blog', 'api'))->toBe([
            'provider' => CoreServiceProvider::class,
            'enabled' => false,
        ])
        ->and($composition->capability('blog', 'cli'))->toBeNull()
        ->and($composition->capabilities('blog'))->toBe([
            'admin' => [
                'provider' => UserServiceProvider::class,
                'enabled' => true,
            ],
            'api' => [
                'provider' => CoreServiceProvider::class,
                'enabled' => false,
            ],
        ]);
});

it('evaluates capabilityProviders across all four package and capability enablement quadrants', function () {
    $catalog = [
        'pkg_on_cap_on' => [
            'id' => 'pkg_on_cap_on',
            'composer_name' => 'laraseed/pkg-on-cap-on',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => [],
        ],
        'pkg_on_cap_off' => [
            'id' => 'pkg_on_cap_off',
            'composer_name' => 'laraseed/pkg-on-cap-off',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => false,
                ],
            ],
            'requires' => [],
        ],
        'pkg_off_cap_on' => [
            'id' => 'pkg_off_cap_on',
            'composer_name' => 'laraseed/pkg-off-cap-on',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => [],
        ],
        'pkg_off_cap_off' => [
            'id' => 'pkg_off_cap_off',
            'composer_name' => 'laraseed/pkg-off-cap-off',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => false,
                ],
            ],
            'requires' => [],
        ],
    ];

    // Enable only pkg_on_cap_on and pkg_on_cap_off
    $composition = new OptionalPackageComposition($catalog, ['pkg_on_cap_on', 'pkg_on_cap_off']);

    // Declared capability exists for all 4
    expect($composition->hasCapability('pkg_on_cap_on', 'admin'))->toBeTrue()
        ->and($composition->hasCapability('pkg_on_cap_off', 'admin'))->toBeTrue()
        ->and($composition->hasCapability('pkg_off_cap_on', 'admin'))->toBeTrue()
        ->and($composition->hasCapability('pkg_off_cap_off', 'admin'))->toBeTrue();

    // Active capability providers only contains provider from pkg_on_cap_on
    expect($composition->capabilityProviders('admin'))->toBe([
        UserServiceProvider::class,
    ]);
});

it('returns capabilityProviders in deterministic topological dependency order', function () {
    $catalog = [
        'base_pkg' => [
            'id' => 'base_pkg',
            'composer_name' => 'laraseed/base-pkg',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => CoreServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => [],
        ],
        'child_pkg' => [
            'id' => 'child_pkg',
            'composer_name' => 'laraseed/child-pkg',
            'provider' => UserServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => ['base_pkg'],
        ],
    ];

    // Pass in reverse order to test topological sorting
    $composition = new OptionalPackageComposition($catalog, ['child_pkg', 'base_pkg']);

    expect($composition->capabilityProviders('admin'))->toBe([
        CoreServiceProvider::class,
        UserServiceProvider::class,
    ]);
});

it('deduplicates capability providers while preserving first dependency order', function () {
    $catalog = [
        'pkg_a' => [
            'id' => 'pkg_a',
            'composer_name' => 'laraseed/pkg-a',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => [],
        ],
        'pkg_b' => [
            'id' => 'pkg_b',
            'composer_name' => 'laraseed/pkg-b',
            'provider' => CoreServiceProvider::class,
            'concord_module' => null,
            'capabilities' => [
                'admin' => [
                    'provider' => UserServiceProvider::class,
                    'enabled' => true,
                ],
            ],
            'requires' => ['pkg_a'],
        ],
    ];

    $composition = new OptionalPackageComposition($catalog, ['pkg_a', 'pkg_b']);

    // UserServiceProvider declared in both pkg_a and pkg_b -> deduplicated to 1 item
    expect($composition->capabilityProviders('admin'))->toBe([
        UserServiceProvider::class,
    ]);
});

it('throws InvalidPackageComposition when querying capabilities on an unknown package', function () {
    $composition = new OptionalPackageComposition(sampleSyntheticPackageCatalog(), ['base_addon']);

    expect(fn () => $composition->hasCapability('non_existent', 'admin'))
        ->toThrow(InvalidPackageComposition::class, 'Unknown Optional package ID [non_existent].')
        ->and(fn () => $composition->capability('non_existent', 'admin'))
        ->toThrow(InvalidPackageComposition::class, 'Unknown Optional package ID [non_existent].')
        ->and(fn () => $composition->capabilities('non_existent'))
        ->toThrow(InvalidPackageComposition::class, 'Unknown Optional package ID [non_existent].');
});
