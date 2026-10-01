<?php

namespace Laraseed\Contacts\Tests\Feature;

require_once dirname(__DIR__) . '/TestCase.php';

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Laraseed\\Contacts\\')) {
        $relative = str_replace('Laraseed\\Contacts\\', '', $class);
        $file = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use Laraseed\Contacts\Admin\Providers\AdminServiceProvider;
use Laraseed\Contacts\Providers\ContactsServiceProvider;
use Laraseed\Contacts\Providers\ModuleServiceProvider;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

class PackageTest extends TestCase
{
    public function test_package_manifest_is_valid_and_matches_contracts(): void
    {
        $manifestPath = base_path('packages/Laraseed/Contacts/composer.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);
        $this->assertSame('laraseed/contacts', $manifest['name']);
        $this->assertSame('library', $manifest['type']);

        $metadata = $manifest['extra']['laraseed'] ?? [];
        $this->assertSame('contacts', $metadata['id']);
        $this->assertSame('optional', $metadata['type']);
        $this->assertSame(ContactsServiceProvider::class, $metadata['provider']);
        $this->assertSame(ModuleServiceProvider::class, $metadata['concord_module']);
        $this->assertArrayHasKey('admin', $metadata['capabilities'] ?? []);
        $this->assertSame(AdminServiceProvider::class, $metadata['capabilities']['admin']['provider']);
        $this->assertTrue($metadata['capabilities']['admin']['enabled']);
    }

    public function test_package_providers_resolve_and_extend_base_contracts(): void
    {
        $this->assertTrue(class_exists(ContactsServiceProvider::class));
        $this->assertTrue(is_subclass_of(ContactsServiceProvider::class, \Illuminate\Support\ServiceProvider::class));

        $this->assertTrue(class_exists(ModuleServiceProvider::class));
        $this->assertTrue(is_subclass_of(ModuleServiceProvider::class, \Konekt\Concord\BaseModuleServiceProvider::class));

        $this->assertTrue(class_exists(AdminServiceProvider::class));
        $this->assertTrue(is_subclass_of(AdminServiceProvider::class, \Illuminate\Support\ServiceProvider::class));
    }

    public function test_package_loads_correctly_through_manifest_loader(): void
    {
        $loader = new OptionalPackageManifestLoader();
        $manifestPath = base_path('packages/Laraseed/Contacts/composer.json');

        $catalog = $loader->load([$manifestPath]);

        $this->assertArrayHasKey('contacts', $catalog);
        $this->assertSame('contacts', $catalog['contacts']['id']);
        $this->assertSame('laraseed/contacts', $catalog['contacts']['composer_name']);
        $this->assertSame(ContactsServiceProvider::class, $catalog['contacts']['provider']);
        $this->assertSame(ModuleServiceProvider::class, $catalog['contacts']['concord_module']);
        $this->assertSame([], $catalog['contacts']['requires']);
        $this->assertArrayHasKey('admin', $catalog['contacts']['capabilities']);
        $this->assertSame(AdminServiceProvider::class, $catalog['contacts']['capabilities']['admin']['provider']);
        $this->assertTrue($catalog['contacts']['capabilities']['admin']['enabled']);
    }

    public function test_disabled_and_enabled_composition_states(): void
    {
        $loader = new OptionalPackageManifestLoader();
        $catalog = $loader->load([base_path('packages/Laraseed/Contacts/composer.json')]);

        // Disabled
        $disabled = new OptionalPackageComposition($catalog, []);
        $this->assertFalse($disabled->isEnabled('contacts'));
        $this->assertSame([], $disabled->providers());
        $this->assertSame([], $disabled->concordModules());
        $this->assertSame([], $disabled->capabilityProviders('admin'));

        // Enabled
        $enabled = new OptionalPackageComposition($catalog, ['contacts']);
        $this->assertTrue($enabled->isEnabled('contacts'));
        $this->assertSame([ContactsServiceProvider::class], $enabled->providers());
        $this->assertSame([ModuleServiceProvider::class], $enabled->concordModules());
        $this->assertSame([AdminServiceProvider::class], $enabled->capabilityProviders('admin'));
    }

    public function test_base_package_has_zero_presentation_dependencies(): void
    {
        $srcDir = base_path('packages/Laraseed/Contacts/src');

        $forbiddenPatterns = [
            'Webkul\Admin',
            'Webkul\DataGrid',
            'AdminServiceProvider',
            'capabilities.admin',
            '<x-admin::',
            'menu.admin',
        ];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            // Exclude intentional Admin capability directory
            if (str_contains($file->getPathname(), '/src/Admin/')) {
                continue;
            }

            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = (string) file_get_contents($file->getPathname());
                foreach ($forbiddenPatterns as $pattern) {
                    $this->assertStringNotContainsString(
                        $pattern,
                        $content,
                        "Forbidden presentation coupling [{$pattern}] found in base package [{$file->getPathname()}]."
                    );
                }
            }
        }
    }
}
