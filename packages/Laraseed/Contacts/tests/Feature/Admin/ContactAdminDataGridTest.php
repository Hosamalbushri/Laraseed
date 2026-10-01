<?php

namespace Laraseed\Contacts\Tests\Feature\Admin;

require_once dirname(__DIR__, 2) . '/TestCase.php';

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laraseed\Contacts\Admin\DataGrids\ContactDataGrid;
use Laraseed\Contacts\Admin\Providers\AdminServiceProvider;
use Laraseed\Contacts\Providers\ContactsServiceProvider;
use Laraseed\Contacts\Providers\ModuleServiceProvider;
use Laraseed\Contacts\Repositories\ContactRepository;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\Core\Acl;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class ContactAdminDataGridTest extends TestCase
{
    use DatabaseTransactions;

    private ContactRepository $repository;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(ContactsServiceProvider::class);
        app()->register(ModuleServiceProvider::class);

        $adminProvider = new AdminServiceProvider($this->app);
        $adminProvider->register();
        $adminProvider->boot();

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

        $this->repository = app(ContactRepository::class);

        $this->admin = User::create([
            'name'            => 'DataGrid Admin',
            'email'           => uniqid('dg-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => 1,
            'view_permission' => 'global',
        ]);
    }

    public function test_datagrid_returns_structured_records_and_columns(): void
    {
        $this->actingAs($this->admin, 'user');

        $this->repository->create([
            'type'       => 'person',
            'first_name' => 'Alexander',
            'last_name'  => 'Hamilton',
            'email'      => 'alex@treasury.gov',
            'phone'      => '+1-555-1789',
            'is_active'  => true,
        ]);

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index'));

        $response->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('columns', $data);
        $this->assertArrayHasKey('records', $data);

        $columnIndices = array_column($data['columns'], 'index');
        $this->assertContains('id', $columnIndices);
        $this->assertContains('canonical_name', $columnIndices);
        $this->assertContains('type', $columnIndices);
        $this->assertContains('email', $columnIndices);
        $this->assertContains('phone', $columnIndices);
        $this->assertContains('is_active', $columnIndices);
        $this->assertContains('created_at', $columnIndices);
    }

    public function test_datagrid_search_by_name_and_email(): void
    {
        $this->actingAs($this->admin, 'user');

        $c1 = $this->repository->create(['type' => 'person', 'first_name' => 'SearchTargetAlpha', 'last_name' => 'Target', 'email' => 'alpha@search.test']);
        $c2 = $this->repository->create(['type' => 'person', 'first_name' => 'SearchTargetBeta', 'last_name' => 'Target', 'email' => 'beta@search.test']);

        // Search for Alpha across all searchable columns
        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index', ['filters' => ['all' => ['SearchTargetAlpha']]]));

        $response->assertOk();
        $records = $response->json('records');
        $names = array_column($records, 'canonical_name');
        $this->assertContains('SearchTargetAlpha Target', $names);
        $this->assertNotContains('SearchTargetBeta Target', $names);
    }

    public function test_datagrid_filter_by_type(): void
    {
        $this->actingAs($this->admin, 'user');

        $person = $this->repository->create(['type' => 'person', 'first_name' => 'PersonOnly', 'last_name' => 'FilterTest', 'email' => 'p@filter.test']);
        $org = $this->repository->create(['type' => 'organization', 'organization_name' => 'OrgOnly Filter Corp', 'email' => 'org@filter.test']);

        // Filter by organization
        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index', ['filters' => ['type' => ['organization']]]));

        $response->assertOk();
        $records = $response->json('records');
        $names = array_column($records, 'canonical_name');
        $this->assertContains('OrgOnly Filter Corp', $names);
        $this->assertNotContains('PersonOnly FilterTest', $names);
    }

    public function test_datagrid_filter_by_active_status(): void
    {
        $this->actingAs($this->admin, 'user');

        $active = $this->repository->create(['type' => 'person', 'first_name' => 'ActiveContact', 'last_name' => 'Filter', 'is_active' => true]);
        $inactive = $this->repository->create(['type' => 'person', 'first_name' => 'InactiveContact', 'last_name' => 'Filter', 'is_active' => false]);

        // Filter by inactive
        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index', ['filters' => ['is_active' => [0]]]));

        $response->assertOk();
        $records = $response->json('records');
        $names = array_column($records, 'canonical_name');
        $this->assertContains('InactiveContact Filter', $names);
        $this->assertNotContains('ActiveContact Filter', $names);
    }

    public function test_datagrid_sorting_by_canonical_name(): void
    {
        $this->actingAs($this->admin, 'user');

        $this->repository->create(['type' => 'person', 'first_name' => 'AAA First', 'last_name' => 'Contact']);
        $this->repository->create(['type' => 'person', 'first_name' => 'ZZZ Last', 'last_name' => 'Contact']);

        $responseAsc = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index', ['sort' => ['column' => 'canonical_name', 'order' => 'asc']]));

        $responseAsc->assertOk();
        $records = $responseAsc->json('records');
        $this->assertNotEmpty($records);
        $firstRecord = reset($records);
        $this->assertSame('AAA First Contact', $firstRecord['canonical_name']);
    }

    public function test_datagrid_actions_are_filtered_by_acl_permissions(): void
    {
        // 1. User with only view permission
        $viewRole = Role::create([
            'name'            => 'View Only Role',
            'description'     => 'Can only view contacts',
            'permission_type' => 'custom',
            'permissions'     => ['contacts'],
        ]);

        $viewUser = User::create([
            'name'            => 'View Only User',
            'email'           => uniqid('view-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => $viewRole->id,
            'view_permission' => 'global',
        ]);

        $contact = $this->repository->create(['type' => 'person', 'first_name' => 'ACL Test', 'last_name' => 'Contact']);

        $this->actingAs($viewUser, 'user');

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index'));

        $response->assertOk();
        $actions = $response->json('actions');
        $actionIndices = array_column($actions, 'index');

        // View action should be present, edit and delete should be omitted
        $this->assertContains('view', $actionIndices);
        $this->assertNotContains('edit', $actionIndices);
        $this->assertNotContains('delete', $actionIndices);
    }
}
