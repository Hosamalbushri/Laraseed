<?php

namespace Laraseed\Contacts\Tests\Feature\Admin;

require_once dirname(__DIR__, 2) . '/TestCase.php';

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laraseed\Contacts\Admin\Providers\AdminServiceProvider;
use Laraseed\Contacts\Providers\ContactsServiceProvider;
use Laraseed\Contacts\Providers\ModuleServiceProvider;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\Core\Acl;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class ContactAdminVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(ContactsServiceProvider::class);
        app()->register(ModuleServiceProvider::class);

        $adminProvider = new AdminServiceProvider($this->app);
        $adminProvider->register();
        $adminProvider->boot();

        // Refresh container ACL to reflect dynamically merged package ACL config
        $this->app->singleton('acl', function () {
            return new class extends Acl {
                public function getRoles(): Collection
                {
                    return collect(config('acl', []))
                        ->mapWithKeys(function ($role) {
                            if (is_array($role['route'])) {
                                return collect($role['route'])->mapWithKeys(function ($route) use ($role) {
                                    return [$route => $role['key']];
                                });
                            } else {
                                return [$role['route'] => $role['key']];
                            }
                        });
                }
            };
        });

        if (! Schema::hasTable('contacts')) {
            $this->artisan('migrate', [
                '--path'     => 'packages/Laraseed/Contacts/src/Database/Migrations',
                '--realpath' => false,
            ]);
        }

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    public function test_runtime_admin_menu_renders_contacts_for_authorized_admin(): void
    {
        $role = Role::create([
            'name'            => 'Contacts Admin ' . uniqid(),
            'permission_type' => 'custom',
            'permissions'     => ['contacts'],
        ]);

        $user = User::create([
            'name'            => 'Contacts Tester',
            'email'           => uniqid('contacts-admin-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => $role->id,
            'view_permission' => 'global',
        ]);

        $this->actingAs($user, 'user');

        $menuItems = menu()->getItems('admin');
        $contactItem = $menuItems->firstWhere(fn ($item) => $item->getKey() === 'contacts');

        $this->assertNotNull($contactItem, 'Contacts menu item must be visible for authorized admin user.');
        $this->assertSame(route('admin.contacts.index'), $contactItem->getUrl());
    }

    public function test_runtime_admin_menu_hides_contacts_for_unauthorized_user(): void
    {
        $role = Role::create([
            'name'            => 'Dashboard Only ' . uniqid(),
            'permission_type' => 'custom',
            'permissions'     => ['dashboard'],
        ]);

        $user = User::create([
            'name'            => 'Dashboard Tester',
            'email'           => uniqid('dashboard-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => $role->id,
            'view_permission' => 'global',
        ]);

        $this->actingAs($user, 'user');

        $menuItems = menu()->getItems('admin');
        $contactItem = $menuItems->firstWhere(fn ($item) => $item->getKey() === 'contacts');

        $this->assertNull($contactItem, 'Contacts menu item must be hidden when user lacks contacts permission.');
    }

    public function test_runtime_super_admin_always_sees_contacts_menu(): void
    {
        $role = Role::create([
            'name'            => 'Super Admin ' . uniqid(),
            'permission_type' => 'all',
            'permissions'     => [],
        ]);

        $user = User::create([
            'name'            => 'Super Admin Tester',
            'email'           => uniqid('super-admin-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => $role->id,
            'view_permission' => 'global',
        ]);

        $this->actingAs($user, 'user');

        $menuItems = menu()->getItems('admin');
        $contactItem = $menuItems->firstWhere(fn ($item) => $item->getKey() === 'contacts');

        $this->assertNotNull($contactItem, 'Contacts menu item must be visible for super admin with permission_type=all.');
    }
}
