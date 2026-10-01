<?php

namespace Laraseed\Contacts\Tests\Feature\Admin;

require_once dirname(__DIR__, 2) . '/TestCase.php';

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laraseed\Contacts\Admin\Providers\AdminServiceProvider;
use Laraseed\Contacts\Events\ContactCreated;
use Laraseed\Contacts\Events\ContactDeleted;
use Laraseed\Contacts\Events\ContactUpdated;
use Laraseed\Contacts\Providers\ContactsServiceProvider;
use Laraseed\Contacts\Providers\ModuleServiceProvider;
use Laraseed\Contacts\Repositories\ContactRepository;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class ContactAdminCrudTest extends TestCase
{
    use DatabaseTransactions;

    private ContactRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(ContactsServiceProvider::class);
        app()->register(ModuleServiceProvider::class);

        $adminProvider = new AdminServiceProvider($this->app);
        $adminProvider->register();
        $adminProvider->boot();

        if (! Schema::hasTable('contacts')) {
            $this->artisan('migrate', [
                '--path'     => 'packages/Laraseed/Contacts/src/Database/Migrations',
                '--realpath' => false,
            ]);
        }

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();

        $this->repository = app(ContactRepository::class);

        $this->user = User::first() ?? User::create([
            'name'            => 'Super Admin',
            'email'           => uniqid('super-') . '@example.test',
            'password'        => Hash::make('password'),
            'status'          => 1,
            'role_id'         => 1,
            'view_permission' => 'global',
        ]);
    }

    public function test_admin_index_displays_contacts_view_and_datagrid_ajax(): void
    {
        $this->actingAs($this->user, 'user');

        $this->repository->create([
            'type'       => 'person',
            'first_name' => 'Sara',
            'last_name'  => 'Connor',
            'email'      => 'sara@example.com',
        ]);

        // 1. HTML View response
        $htmlResponse = $this->get(route('admin.contacts.index'));
        $htmlResponse->assertOk();
        $htmlResponse->assertSee(trans('contacts_admin::app.admin.title'));

        // 2. DataGrid AJAX JSON response
        $ajaxResponse = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('admin.contacts.index'));

        $ajaxResponse->assertOk();
        $ajaxResponse->assertJsonStructure([
            'id',
            'columns',
            'actions',
            'mass_actions',
            'records',
            'meta',
        ]);

        $records = $ajaxResponse->json('records');
        $this->assertNotEmpty($records);
        $names = array_column($records, 'canonical_name');
        $this->assertContains('Sara Connor', $names);
    }

    public function test_admin_show_displays_contact_details(): void
    {
        $this->actingAs($this->user, 'user');

        $contact = $this->repository->create([
            'type'              => 'organization',
            'organization_name' => 'Acme Corporation Global',
            'tax_number'        => 'TAX-998877',
            'email'             => 'contact@acmeglobal.test',
            'phone'             => '+1-555-0199',
            'city'              => 'Dubai',
            'country_code'      => 'AE',
            'is_active'         => true,
            'notes'             => 'Enterprise VIP account',
        ]);

        $response = $this->get(route('admin.contacts.show', $contact->id));

        $response->assertOk();
        $response->assertSee('Acme Corporation Global');
        $response->assertSee('TAX-998877');
        $response->assertSee('contact@acmeglobal.test');
        $response->assertSee('+1-555-0199');
        $response->assertSee('Dubai');
        $response->assertSee('Enterprise VIP account');
    }

    public function test_admin_create_person_contact_via_post_dispatches_events(): void
    {
        $this->actingAs($this->user, 'user');
        Event::fake([ContactCreated::class]);

        $payload = [
            'type'          => 'person',
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'email'         => 'john.doe@example.com',
            'phone'         => '+123456789',
            'is_active'     => '1',
        ];

        $response = $this->post(route('admin.contacts.store'), $payload);

        $response->assertRedirect(route('admin.contacts.index'));
        $response->assertSessionHas('success');

        $contact = $this->repository->findOneByField('email', 'john.doe@example.com');
        $this->assertNotNull($contact);
        $this->assertSame('John Doe', $contact->name);
        $this->assertSame('person', $contact->type);
        $this->assertTrue((bool) $contact->is_active);

        Event::assertDispatched(ContactCreated::class, function ($event) use ($contact) {
            return $event->contact->id === $contact->id;
        });
    }

    public function test_admin_create_organization_contact_with_arabic_unicode(): void
    {
        $this->actingAs($this->user, 'user');

        $payload = [
            'type'              => 'organization',
            'organization_name' => 'شركة الأمل للتجارة',
            'email'             => 'alamal@example.ye',
            'country_code'      => 'YE',
            'city'              => 'صنعاء',
            'is_active'         => '1',
        ];

        $response = $this->post(route('admin.contacts.store'), $payload);

        $response->assertRedirect(route('admin.contacts.index'));

        $contact = $this->repository->findOneByField('email', 'alamal@example.ye');
        $this->assertNotNull($contact);
        $this->assertSame('شركة الأمل للتجارة', $contact->name);
        $this->assertSame('صنعاء', $contact->city);
        $this->assertSame('YE', $contact->country_code);
    }

    public function test_admin_form_validation_errors_redirect_back_with_session_errors(): void
    {
        $this->actingAs($this->user, 'user');

        $response = $this->post(route('admin.contacts.store'), [
            'type'  => 'person',
            'email' => 'invalid-email',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_domain_validation_errors_redirect_back_with_session_errors(): void
    {
        $this->actingAs($this->user, 'user');

        $response = $this->post(route('admin.contacts.store'), [
            'type'  => 'person',
            'email' => 'valid@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name']);
    }

    public function test_admin_cannot_override_canonical_name(): void
    {
        $this->actingAs($this->user, 'user');

        $payload = [
            'type'       => 'person',
            'name'       => 'Malicious Overridden Name',
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
            'email'      => 'alice@example.com',
        ];

        $this->post(route('admin.contacts.store'), $payload);

        $contact = $this->repository->findOneByField('email', 'alice@example.com');
        $this->assertSame('Alice Smith', $contact->name);
    }

    public function test_admin_ignores_role_flags_maintaining_pure_party_domain(): void
    {
        $this->actingAs($this->user, 'user');

        $payload = [
            'type'        => 'person',
            'first_name'  => 'Bob',
            'last_name'   => 'Builder',
            'email'       => 'bob@example.com',
            'is_customer' => 1,
            'is_vendor'   => 1,
        ];

        $this->post(route('admin.contacts.store'), $payload);

        $contact = $this->repository->findOneByField('email', 'bob@example.com');
        $this->assertArrayNotHasKey('is_customer', $contact->toArray());
        $this->assertArrayNotHasKey('is_vendor', $contact->toArray());
    }

    public function test_admin_edit_displays_existing_contact(): void
    {
        $this->actingAs($this->user, 'user');

        $contact = $this->repository->create([
            'type'       => 'person',
            'first_name' => 'Charlie',
            'last_name'  => 'Brown',
            'email'      => 'charlie@example.com',
        ]);

        $response = $this->get(route('admin.contacts.edit', $contact->id));

        $response->assertOk();
        $response->assertSee('Charlie');
        $response->assertSee('Brown');
        $response->assertSee('charlie@example.com');
    }

    public function test_admin_update_contact_applies_changes_and_dispatches_event(): void
    {
        $this->actingAs($this->user, 'user');
        Event::fake([ContactUpdated::class]);

        $contact = $this->repository->create([
            'type'       => 'person',
            'first_name' => 'David',
            'last_name'  => 'Miller',
            'email'      => 'david@example.com',
        ]);

        $response = $this->put(route('admin.contacts.update', $contact->id), [
            'first_name' => 'David Jr.',
            'last_name'  => 'Miller',
            'job_title'  => 'Director',
        ]);

        $response->assertRedirect(route('admin.contacts.index'));
        $response->assertSessionHas('success');

        $fresh = $contact->fresh();
        $this->assertSame('David Jr. Miller', $fresh->name);
        $this->assertSame('Director', $fresh->job_title);

        Event::assertDispatched(ContactUpdated::class, function ($event) use ($contact) {
            return $event->contact->id === $contact->id;
        });
    }

    public function test_admin_destroy_deletes_contact_and_dispatches_event(): void
    {
        $this->actingAs($this->user, 'user');
        Event::fake([ContactDeleted::class]);

        $contact = $this->repository->create([
            'type'       => 'person',
            'first_name' => 'Eva',
            'last_name'  => 'Green',
            'email'      => 'eva@example.com',
        ]);

        $response = $this->delete(route('admin.contacts.destroy', $contact->id));

        $response->assertRedirect(route('admin.contacts.index'));
        $this->assertNull($this->repository->find($contact->id));

        Event::assertDispatched(ContactDeleted::class, function ($event) use ($contact) {
            return $event->contactId === $contact->id;
        });
    }

    public function test_admin_mass_destroy_deletes_multiple_contacts(): void
    {
        $this->actingAs($this->user, 'user');

        $c1 = $this->repository->create(['type' => 'person', 'first_name' => 'M1', 'last_name' => 'Test']);
        $c2 = $this->repository->create(['type' => 'person', 'first_name' => 'M2', 'last_name' => 'Test']);

        $response = $this->post(route('admin.contacts.mass_destroy'), [
            'indices' => [$c1->id, $c2->id],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message']);
        $this->assertNull($this->repository->find($c1->id));
        $this->assertNull($this->repository->find($c2->id));
    }

    public function test_admin_mass_update_updates_status_of_multiple_contacts(): void
    {
        $this->actingAs($this->user, 'user');

        $c1 = $this->repository->create(['type' => 'person', 'first_name' => 'U1', 'last_name' => 'Test', 'is_active' => true]);
        $c2 = $this->repository->create(['type' => 'person', 'first_name' => 'U2', 'last_name' => 'Test', 'is_active' => true]);

        $response = $this->post(route('admin.contacts.mass_update'), [
            'indices' => [$c1->id, $c2->id],
            'value'   => 0,
        ]);

        $response->assertOk();
        $this->assertFalse((bool) $c1->fresh()->is_active);
        $this->assertFalse((bool) $c2->fresh()->is_active);
    }
}
