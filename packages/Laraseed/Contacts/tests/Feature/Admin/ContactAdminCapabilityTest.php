<?php

namespace Laraseed\Contacts\Tests\Feature\Admin;

require_once dirname(__DIR__, 2) . '/TestCase.php';

use Illuminate\Support\Facades\Route;
use Laraseed\Contacts\Admin\Providers\AdminServiceProvider;
use Laraseed\Contacts\Providers\ContactsServiceProvider;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\Core\Packages\OptionalPackageComposition;

class ContactAdminCapabilityTest extends TestCase
{
    public function test_quadrant_1_contacts_enabled_and_admin_capability_enabled_activates_admin_provider(): void
    {
        $catalog = [
            'contacts' => [
                'id'            => 'contacts',
                'composer_name' => 'laraseed/contacts',
                'provider'      => ContactsServiceProvider::class,
                'concord_module'=> null,
                'capabilities'  => [
                    'admin' => [
                        'provider' => AdminServiceProvider::class,
                        'enabled'  => true,
                    ],
                ],
                'requires'      => [],
            ],
        ];

        $composition = new OptionalPackageComposition($catalog, ['contacts']);

        $this->assertTrue($composition->hasCapability('contacts', 'admin'));
        $this->assertSame(
            [AdminServiceProvider::class],
            $composition->capabilityProviders('admin')
        );
    }

    public function test_quadrant_2_contacts_enabled_and_admin_capability_disabled_deactivates_admin_provider(): void
    {
        $catalog = [
            'contacts' => [
                'id'            => 'contacts',
                'composer_name' => 'laraseed/contacts',
                'provider'      => ContactsServiceProvider::class,
                'concord_module'=> null,
                'capabilities'  => [
                    'admin' => [
                        'provider' => AdminServiceProvider::class,
                        'enabled'  => false,
                    ],
                ],
                'requires'      => [],
            ],
        ];

        $composition = new OptionalPackageComposition($catalog, ['contacts']);

        $this->assertTrue($composition->hasCapability('contacts', 'admin'));
        $this->assertSame([], $composition->capabilityProviders('admin'));
    }

    public function test_quadrant_3_contacts_disabled_and_admin_capability_enabled_deactivates_admin_provider(): void
    {
        $catalog = [
            'contacts' => [
                'id'            => 'contacts',
                'composer_name' => 'laraseed/contacts',
                'provider'      => ContactsServiceProvider::class,
                'concord_module'=> null,
                'capabilities'  => [
                    'admin' => [
                        'provider' => AdminServiceProvider::class,
                        'enabled'  => true,
                    ],
                ],
                'requires'      => [],
            ],
        ];

        $composition = new OptionalPackageComposition($catalog, []);

        $this->assertSame([], $composition->capabilityProviders('admin'));
    }

    public function test_quadrant_4_contacts_disabled_and_admin_capability_disabled_deactivates_admin_provider(): void
    {
        $catalog = [
            'contacts' => [
                'id'            => 'contacts',
                'composer_name' => 'laraseed/contacts',
                'provider'      => ContactsServiceProvider::class,
                'concord_module'=> null,
                'capabilities'  => [
                    'admin' => [
                        'provider' => AdminServiceProvider::class,
                        'enabled'  => false,
                    ],
                ],
                'requires'      => [],
            ],
        ];

        $composition = new OptionalPackageComposition($catalog, []);

        $this->assertSame([], $composition->capabilityProviders('admin'));
    }

    public function test_contacts_base_provider_does_not_probe_or_register_admin(): void
    {
        $reflector = new \ReflectionClass(ContactsServiceProvider::class);
        $source = (string) file_get_contents($reflector->getFileName());

        $this->assertStringNotContainsString('AdminServiceProvider', $source);
        $this->assertStringNotContainsString('class_exists', $source);
    }

    public function test_admin_provider_does_not_use_runtime_probing(): void
    {
        $reflector = new \ReflectionClass(AdminServiceProvider::class);
        $source = (string) file_get_contents($reflector->getFileName());

        $this->assertStringNotContainsString('class_exists', $source);
    }

    public function test_admin_provider_registers_routes_menu_acl_views_translations(): void
    {
        $provider = new AdminServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();

        $this->assertTrue(Route::has('admin.contacts.index'));
        $this->assertTrue(Route::has('admin.contacts.create'));
        $this->assertTrue(Route::has('admin.contacts.store'));
        $this->assertTrue(Route::has('admin.contacts.edit'));
        $this->assertTrue(Route::has('admin.contacts.update'));
        $this->assertTrue(Route::has('admin.contacts.delete'));
        $this->assertTrue(Route::has('admin.contacts.destroy'));

        $this->assertNotEmpty(config('menu.admin'));
        $this->assertNotEmpty(config('acl'));
    }
}
