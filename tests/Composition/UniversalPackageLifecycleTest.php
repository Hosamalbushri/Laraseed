<?php

namespace Tests\Composition;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\InteractsWithOptionalPackageComposition;
use Tests\TestCase;
use Webkul\Core\Exceptions\InvalidPackageComposition;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\User\Providers\UserServiceProvider;

class UniversalPackageLifecycleTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithOptionalPackageComposition;

    public function test_manifest_discovery_identifies_optional_packages_and_ignores_foundation(): void
    {
        $manifestFiles = glob(base_path('packages/*/*/composer.json')) ?: [];
        $optionalManifests = [];

        foreach ($manifestFiles as $manifestFile) {
            $data = json_decode((string) file_get_contents($manifestFile), true);
            if (is_array($data) && ($data['extra']['laraseed']['type'] ?? null) === 'optional') {
                $optionalManifests[] = $manifestFile;
            }
        }

        $catalog = (new OptionalPackageManifestLoader)->load($optionalManifests);

        $this->assertArrayHasKey('contacts', $catalog);
        $this->assertSame('contacts', $catalog['contacts']['id']);
        $this->assertSame('laraseed/contacts', $catalog['contacts']['composer_name']);
        $this->assertSame('Laraseed\Contacts\Providers\ContactsServiceProvider', $catalog['contacts']['provider']);
        $this->assertSame('Laraseed\Contacts\Providers\ModuleServiceProvider', $catalog['contacts']['concord_module']);
        $this->assertArrayHasKey('admin', $catalog['contacts']['capabilities']);
        $this->assertSame('Laraseed\Contacts\Admin\Providers\AdminServiceProvider', $catalog['contacts']['capabilities']['admin']['provider']);

        // Assert Foundation packages are not in optional catalog
        $this->assertArrayNotHasKey('core', $catalog);
        $this->assertArrayNotHasKey('admin', $catalog);
        $this->assertArrayNotHasKey('user', $catalog);
        $this->assertArrayNotHasKey('datagrid', $catalog);
        $this->assertArrayNotHasKey('package_generator', $catalog);
    }

    public function test_controlled_second_package_quad_state_lifecycle(): void
    {
        $syntheticCatalog = [
            'contacts' => [
                'id' => 'contacts',
                'composer_name' => 'laraseed/contacts',
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
            'second_fixture' => [
                'id' => 'second_fixture',
                'composer_name' => 'laraseed/second-fixture',
                'provider' => CoreServiceProvider::class,
                'concord_module' => null,
                'capabilities' => [
                    'admin' => [
                        'provider' => UserServiceProvider::class,
                        'enabled' => true,
                    ],
                ],
                'requires' => ['contacts'],
            ],
        ];

        // 1. Foundation-only state (neither enabled)
        $comp0 = new OptionalPackageComposition($syntheticCatalog, []);
        $this->assertSame([], $comp0->enabledPackages());
        $this->assertSame([], $comp0->providers());
        $this->assertSame([], $comp0->capabilityProviders('admin'));

        // 2. Contacts-only state (fixture installed but disabled)
        $comp1 = new OptionalPackageComposition($syntheticCatalog, ['contacts']);
        $this->assertSame(['contacts'], $comp1->enabledPackages());
        $this->assertTrue($comp1->isEnabled('contacts'));
        $this->assertFalse($comp1->isEnabled('second_fixture'));
        $this->assertSame([CoreServiceProvider::class], $comp1->providers());
        $this->assertSame([UserServiceProvider::class], $comp1->capabilityProviders('admin'));

        // 3. Both enabled (dependency order respected)
        $comp2 = new OptionalPackageComposition($syntheticCatalog, ['second_fixture', 'contacts']);
        $this->assertSame(['contacts', 'second_fixture'], $comp2->enabledPackages());
        $this->assertTrue($comp2->isEnabled('contacts'));
        $this->assertTrue($comp2->isEnabled('second_fixture'));
        $this->assertSame(['second_fixture'], $comp2->enabledDependents('contacts'));
        $this->assertFalse($comp2->canDisable('contacts'));
        $this->assertTrue($comp2->canDisable('second_fixture'));

        // 4. Invalid state: Second fixture enabled without required Contacts
        $this->expectException(InvalidPackageComposition::class);
        $this->expectExceptionMessage('Optional package "second_fixture" requires enabled package "contacts".');
        new OptionalPackageComposition($syntheticCatalog, ['second_fixture']);
    }

    public function test_runtime_cli_composition_isolation_across_environments(): void
    {
        // 1. Foundation-only CLI execution
        $foundationResult = $this->runCompositionCommand([], ['laraseed:packages']);
        $this->assertSame(0, $foundationResult['exit_code']);
        $this->assertStringContainsString('Foundation only', $foundationResult['output']);
        $this->assertStringContainsString('DISABLED', $foundationResult['output']);

        $foundationRoutes = $this->routesForComposition([]);
        $this->assertCount(68, $foundationRoutes);

        // 2. Contacts-enabled CLI execution
        $contactsResult = $this->runCompositionCommand(['contacts'], ['laraseed:packages']);
        $this->assertSame(0, $contactsResult['exit_code']);
        $this->assertStringContainsString('ACTIVE', $contactsResult['output']);

        $contactsRoutes = $this->routesForComposition(['contacts']);
        $this->assertCount(83, $contactsRoutes);
    }

    public function test_config_caching_invariance(): void
    {
        // Assert config values are deterministic and serializable
        $config = require base_path('config/laraseed.php');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('optional_packages', $config);
        $this->assertArrayHasKey('enabled', $config['optional_packages']);
        $this->assertArrayHasKey('catalog', $config['optional_packages']);
        $this->assertArrayHasKey('providers', $config['optional_packages']);
        $this->assertArrayHasKey('concord_modules', $config['optional_packages']);

        $concordConfig = require base_path('config/concord.php');
        $this->assertIsArray($concordConfig);
        $this->assertArrayHasKey('modules', $concordConfig);
    }
}
