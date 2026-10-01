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

class ContactAdminAclAndMenuTest extends TestCase
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
                '--path' => 'packages/Laraseed/Contacts/src/Database/Migrations',
                '--realpath' => false,
            ]);
        }

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    private function createAdminUser(array $permissions = []): User
    {
        $role = Role::create([
            'name'            => 'Admin Role ' . uniqid(),
            'permission_type' => 'custom',
            'permissions'     => $permissions,
        ]);

        return User::create([
            'name'            => 'Admin Tester',
            'email'           => uniqid('admin-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => $role->id,
            'view_permission' => 'global',
        ]);
    }

    public function test_unauthenticated_admin_request_redirects_to_login(): void
    {
        $response = $this->get(route('admin.contacts.index'));

        $response->assertRedirect(route('admin.session.create'));
    }

    public function test_unauthorized_admin_user_is_rejected_by_bouncer(): void
    {
        $user = $this->createAdminUser(['dashboard']);
        $this->actingAs($user, 'user');

        $response = $this->get(route('admin.contacts.index'));

        $response->assertUnauthorized();
    }

    public function test_authorized_admin_user_with_contacts_permission_can_access_index(): void
    {
        $user = $this->createAdminUser(['contacts']);
        $this->actingAs($user, 'user');

        $response = $this->get(route('admin.contacts.index'));

        $response->assertOk();
    }

    public function test_user_without_create_permission_cannot_access_create_or_store(): void
    {
        $user = $this->createAdminUser(['contacts']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.contacts.create'))->assertUnauthorized();
        $this->post(route('admin.contacts.store'), [
            'type'       => 'person',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ])->assertUnauthorized();
    }

    public function test_user_with_create_permission_can_access_create_form(): void
    {
        $user = $this->createAdminUser(['contacts', 'contacts.create']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.contacts.create'))->assertOk();
    }

    public function test_user_without_edit_permission_cannot_access_edit_or_update(): void
    {
        $user = $this->createAdminUser(['contacts']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.contacts.edit', 1))->assertUnauthorized();
        $this->put(route('admin.contacts.update', 1), [
            'first_name' => 'Updated',
        ])->assertUnauthorized();
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $user = $this->createAdminUser(['contacts']);
        $this->actingAs($user, 'user');

        $this->delete(route('admin.contacts.delete', 1))->assertUnauthorized();
        $this->delete(route('admin.contacts.destroy', 1))->assertUnauthorized();
    }

    public function test_menu_configuration_contains_contacts_entry(): void
    {
        $menu = config('menu.admin', []);

        $contactMenu = null;
        foreach ($menu as $item) {
            if (isset($item['key']) && $item['key'] === 'contacts') {
                $contactMenu = $item;
                break;
            }
        }

        $this->assertNotNull($contactMenu);
        $this->assertSame('admin.contacts.index', $contactMenu['route']);
        $this->assertSame('contacts_admin::app.menu.contacts', $contactMenu['name']);
        $this->assertSame('icon-user', $contactMenu['icon-class']);
    }

    public function test_acl_configuration_contains_all_contacts_keys(): void
    {
        $acl = config('acl', []);
        $keys = array_column($acl, 'key');

        $this->assertContains('contacts', $keys);
        $this->assertContains('contacts.create', $keys);
        $this->assertContains('contacts.edit', $keys);
        $this->assertContains('contacts.delete', $keys);
    }
}
