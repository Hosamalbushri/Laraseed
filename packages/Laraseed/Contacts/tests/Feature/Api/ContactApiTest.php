<?php

namespace Laraseed\Contacts\Tests\Feature\Api;

require_once dirname(__DIR__, 2) . '/TestCase.php';

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Laraseed\Contacts\Events\ContactCreated;
use Laraseed\Contacts\Events\ContactDeleted;
use Laraseed\Contacts\Events\ContactUpdated;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Repositories\ContactRepository;
use Laraseed\Contacts\Tests\TestCase;
use Webkul\User\Models\User;

class ContactApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected ContactRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $provider = app()->register(\Laraseed\Contacts\Providers\ContactsServiceProvider::class);
        app()->register(\Laraseed\Contacts\Providers\ModuleServiceProvider::class);

        if (file_exists(base_path('packages/Laraseed/Contacts/src/Routes/api.php'))) {
            require base_path('packages/Laraseed/Contacts/src/Routes/api.php');
            app('router')->getRoutes()->refreshNameLookups();
            app('router')->getRoutes()->refreshActionLookups();
        }

        if (! Schema::hasTable('contacts')) {
            $this->artisan('migrate', [
                '--path' => 'packages/Laraseed/Contacts/src/Database/Migrations',
                '--realpath' => false,
            ]);
        }

        $this->repository = app(ContactRepository::class);
        $this->user = User::first() ?? User::create([
            'name' => 'API Admin',
            'email' => 'api-admin@example.test',
            'password' => bcrypt('password123'),
            'role_id' => 1,
            'status' => 1,
            'view_permission' => 'global',
        ]);
    }

    protected function authenticate(): void
    {
        Sanctum::actingAs($this->user, ['*'], 'user');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('contacts.api.index'))
            ->assertUnauthorized();

        $this->postJson(route('contacts.api.store'), [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Anonymous',
        ])->assertUnauthorized();
    }

    public function test_index_returns_paginated_contacts_with_resource_contract(): void
    {
        $this->authenticate();

        $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Index',
            'last_name' => 'Person',
            'email' => 'index.person@example.com',
        ]);

        $response = $this->getJson(route('contacts.api.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'name',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'job_title',
                        'organization_name',
                        'tax_number',
                        'email',
                        'phone',
                        'mobile',
                        'website',
                        'address_line_1',
                        'address_line_2',
                        'city',
                        'state',
                        'postal_code',
                        'country_code',
                        'notes',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_index_honors_pagination_limits_and_bounds(): void
    {
        $this->authenticate();

        $response = $this->getJson(route('contacts.api.index', ['per_page' => 5]))
            ->assertOk();

        $this->assertSame(5, $response->json('meta.per_page'));

        // Over-limit requests should be bounded at max 100
        $overLimit = $this->getJson(route('contacts.api.index', ['per_page' => 500]))
            ->assertOk();

        $this->assertSame(100, $overLimit->json('meta.per_page'));
    }

    public function test_index_filters_by_type_and_is_active(): void
    {
        $this->authenticate();

        $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Active',
            'last_name' => 'Person',
            'is_active' => true,
        ]);

        $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Active Org',
            'is_active' => true,
        ]);

        $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Inactive Org',
            'is_active' => false,
        ]);

        $orgOnly = $this->getJson(route('contacts.api.index', ['type' => Contact::TYPE_ORGANIZATION]))
            ->assertOk();

        foreach ($orgOnly->json('data') as $item) {
            $this->assertSame(Contact::TYPE_ORGANIZATION, $item['type']);
        }

        $activeOnly = $this->getJson(route('contacts.api.index', ['is_active' => 'true']))
            ->assertOk();

        foreach ($activeOnly->json('data') as $item) {
            $this->assertTrue($item['is_active']);
        }
    }

    public function test_store_creates_person_contact_with_normalization_and_dispatches_event(): void
    {
        $this->authenticate();
        Event::fake([ContactCreated::class]);

        $payload = [
            'type' => Contact::TYPE_PERSON,
            'first_name' => '  Fahad  ',
            'middle_name' => 'Saud',
            'last_name' => 'Al-Dawsari',
            'email' => '  FAHAD.DAWSARI@EXAMPLE.COM  ',
            'country_code' => '  sa  ',
            'phone' => '  +966501234567  ',
            'is_active' => true,
        ];

        $response = $this->postJson(route('contacts.api.store'), $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => Contact::TYPE_PERSON,
                    'name' => 'Fahad Saud Al-Dawsari',
                    'first_name' => 'Fahad',
                    'last_name' => 'Al-Dawsari',
                    'email' => 'fahad.dawsari@example.com',
                    'country_code' => 'SA',
                    'phone' => '+966501234567',
                    'is_active' => true,
                ],
            ]);

        $id = $response->json('data.id');
        $this->assertNotNull($id);

        Event::assertDispatched(ContactCreated::class, function (ContactCreated $event) use ($id) {
            return $event->contact->id === $id;
        });
    }

    public function test_store_creates_organization_contact_with_arabic_unicode(): void
    {
        $this->authenticate();
        Event::fake([ContactCreated::class]);

        $payload = [
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'شركة الرياض المتقدمة لتقنية المعلومات',
            'tax_number' => '300099988877703',
            'email' => 'info@riyadh-tech.sa',
            'city' => 'الرياض',
            'country_code' => 'SA',
        ];

        $this->postJson(route('contacts.api.store'), $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => Contact::TYPE_ORGANIZATION,
                    'name' => 'شركة الرياض المتقدمة لتقنية المعلومات',
                    'organization_name' => 'شركة الرياض المتقدمة لتقنية المعلومات',
                    'tax_number' => '300099988877703',
                    'email' => 'info@riyadh-tech.sa',
                    'city' => 'الرياض',
                    'country_code' => 'SA',
                ],
            ]);

        Event::assertDispatched(ContactCreated::class);
    }

    public function test_store_validation_errors_return_422(): void
    {
        $this->authenticate();

        // Missing type
        $this->postJson(route('contacts.api.store'), [
            'name' => 'Invalid Record',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);

        // Invalid email format
        $this->postJson(route('contacts.api.store'), [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Bad',
            'last_name' => 'Email',
            'email' => 'invalid-email-address',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);

        // Invalid country code length
        $this->postJson(route('contacts.api.store'), [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Bad',
            'last_name' => 'Country',
            'country_code' => 'SAU',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['country_code']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $this->authenticate();

        $this->postJson(route('contacts.api.store'), [
            'type' => 'customer', // forbidden business role as type
            'name' => 'Customer Party',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    public function test_show_returns_contact_resource(): void
    {
        $this->authenticate();

        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Show',
            'last_name' => 'Me',
            'email' => 'show.me@example.com',
        ]);

        $this->getJson(route('contacts.api.show', $contact->id))
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $contact->id,
                    'name' => 'Show Me',
                    'email' => 'show.me@example.com',
                ],
            ]);
    }

    public function test_show_returns_404_for_non_existent_contact(): void
    {
        $this->authenticate();

        $this->getJson(route('contacts.api.show', 999999))
            ->assertNotFound();
    }

    public function test_update_applies_partial_changes_and_dispatches_event(): void
    {
        $this->authenticate();
        Event::fake([ContactUpdated::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Abdulaziz',
            'last_name' => 'Al-Haqeel',
            'email' => 'abdulaziz@example.com',
            'city' => 'Dammam',
        ]);

        $this->patchJson(route('contacts.api.update', $contact->id), [
            'phone' => '+966509876543',
            'city' => 'Khobar',
        ])->assertOk()
          ->assertJson([
              'data' => [
                  'id' => $contact->id,
                  'first_name' => 'Abdulaziz',
                  'last_name' => 'Al-Haqeel',
                  'email' => 'abdulaziz@example.com',
                  'phone' => '+966509876543',
                  'city' => 'Khobar',
              ],
          ]);

        Event::assertDispatched(ContactUpdated::class, function (ContactUpdated $event) use ($contact) {
            return $event->contact->id === $contact->id
                && array_key_exists('phone', $event->changes)
                && array_key_exists('city', $event->changes);
        });
    }

    public function test_update_type_transition_cleanses_inapplicable_fields(): void
    {
        $this->authenticate();

        $person = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Khalil',
            'middle_name' => 'Ibrahim',
            'last_name' => 'Al-Ghamdi',
            'job_title' => 'Chief Architect',
        ]);

        $response = $this->putJson(route('contacts.api.update', $person->id), [
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Al-Ghamdi Tech Consultancy',
            'tax_number' => '311122233344403',
        ])->assertOk();

        $this->assertSame(Contact::TYPE_ORGANIZATION, $response->json('data.type'));
        $this->assertSame('Al-Ghamdi Tech Consultancy', $response->json('data.name'));
        $this->assertSame('311122233344403', $response->json('data.tax_number'));
        $this->assertNull($response->json('data.first_name'));
        $this->assertNull($response->json('data.middle_name'));
        $this->assertNull($response->json('data.last_name'));
        $this->assertNull($response->json('data.job_title'));
    }

    public function test_destroy_deletes_contact_and_dispatches_event(): void
    {
        $this->authenticate();
        Event::fake([ContactDeleted::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Delete',
            'last_name' => 'Target',
            'email' => 'delete.target@example.com',
        ]);

        $id = (int) $contact->id;

        $this->deleteJson(route('contacts.api.destroy', $id))
            ->assertNoContent();

        $this->assertNull($this->repository->find($id));

        Event::assertDispatched(ContactDeleted::class, function (ContactDeleted $event) use ($id) {
            return $event->contactId === $id
                && $event->snapshot['name'] === 'Delete Target';
        });
    }

    public function test_destroy_returns_404_for_unknown_contact(): void
    {
        $this->authenticate();

        $this->deleteJson(route('contacts.api.destroy', 999999))
            ->assertNotFound();
    }

    public function test_contacts_api_routes_are_package_owned_and_point_to_package_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('contacts.api.index');
        $this->assertNotNull($route);
        $this->assertSame('api/contacts', $route->uri());
        $this->assertSame(
            'Laraseed\Contacts\Http\Controllers\ContactController@index',
            $route->getActionName()
        );
    }

    public function test_store_derives_canonical_name_overriding_malicious_client_name(): void
    {
        $this->authenticate();

        $response = $this->postJson(route('contacts.api.store'), [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'RealFirst',
            'last_name' => 'RealLast',
            'name' => 'Fake Spoofed Name',
        ])->assertCreated();

        // Repository canonical name policy overrides spoofed client name
        $this->assertSame('RealFirst RealLast', $response->json('data.name'));
    }

    public function test_store_rejects_or_ignores_role_flags_maintaining_pure_party_domain(): void
    {
        $this->authenticate();

        $response = $this->postJson(route('contacts.api.store'), [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Pure',
            'last_name' => 'Party',
            'is_customer' => true,
            'is_supplier' => true,
            'is_employee' => true,
        ])->assertCreated();

        $contact = $this->repository->find($response->json('data.id'));
        $this->assertNotNull($contact);
        $this->assertFalse(Schema::hasColumn('contacts', 'is_customer'));
        $this->assertFalse(Schema::hasColumn('contacts', 'is_supplier'));
        $this->assertFalse(Schema::hasColumn('contacts', 'is_employee'));
    }
}
