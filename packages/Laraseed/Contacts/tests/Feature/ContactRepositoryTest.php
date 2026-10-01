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

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Laraseed\Contacts\Contracts\Contact as ContactContract;
use Laraseed\Contacts\Events\ContactCreated;
use Laraseed\Contacts\Events\ContactDeleted;
use Laraseed\Contacts\Events\ContactUpdated;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Repositories\ContactRepository;
use Laraseed\Contacts\Tests\TestCase;

class ContactRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected ContactRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(\Laraseed\Contacts\Providers\ContactsServiceProvider::class);
        app()->register(\Laraseed\Contacts\Providers\ModuleServiceProvider::class);

        if (! Schema::hasTable('contacts')) {
            $this->artisan('migrate', [
                '--path' => 'packages/Laraseed/Contacts/src/Database/Migrations',
                '--realpath' => false,
            ]);
        }

        $this->repository = app(ContactRepository::class);
    }

    public function test_repository_resolves_canonical_contract_model(): void
    {
        $this->assertInstanceOf(ContactRepository::class, $this->repository);
        $this->assertSame('Laraseed\Contacts\Contracts\Contact', $this->repository->model());
        $this->assertInstanceOf(Contact::class, $this->repository->getModel());
        $this->assertInstanceOf(ContactContract::class, $this->repository->getModel());
    }

    public function test_attribute_normalization(): void
    {
        $input = [
            'type' => Contact::TYPE_PERSON,
            'first_name' => '  Omar  ',
            'last_name' => '  Al-Ghamdi  ',
            'email' => '  OMAR.GHAMDI@EXAMPLE.COM  ',
            'country_code' => '  sa  ',
            'phone' => '  +966509998877  ',
            'notes' => '   ',
            'city' => '',
            'is_active' => '1',
        ];

        $normalized = $this->repository->normalizeAttributes($input);

        $this->assertSame('Omar', $normalized['first_name']);
        $this->assertSame('Al-Ghamdi', $normalized['last_name']);
        $this->assertSame('omar.ghamdi@example.com', $normalized['email']);
        $this->assertSame('SA', $normalized['country_code']);
        $this->assertSame('+966509998877', $normalized['phone']);
        $this->assertNull($normalized['notes']);
        $this->assertNull($normalized['city']);
        $this->assertTrue($normalized['is_active']);
    }

    public function test_canonical_name_derivation_for_person(): void
    {
        // Case 1: First, Middle, Last
        $name1 = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Khalid',
            'middle_name' => 'Bin',
            'last_name' => 'Waleed',
        ]);
        $this->assertSame('Khalid Bin Waleed', $name1);

        // Case 2: Only First Name
        $name2 = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Tariq',
        ]);
        $this->assertSame('Tariq', $name2);

        // Case 3: Explicit display name fallback
        $name3 = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'name' => 'Special Persona',
        ]);
        $this->assertSame('Special Persona', $name3);
    }

    public function test_canonical_name_derivation_for_organization(): void
    {
        $name1 = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Saudi Aramco Oil Co.',
        ]);
        $this->assertSame('Saudi Aramco Oil Co.', $name1);

        $name2 = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_ORGANIZATION,
            'name' => 'Direct Org Name',
        ]);
        $this->assertSame('Direct Org Name', $name2);
    }

    public function test_create_person_contact_persists_and_dispatches_created_event(): void
    {
        Event::fake([ContactCreated::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Sami',
            'middle_name' => 'Ahmed',
            'last_name' => 'Al-Jaber',
            'job_title' => 'Director',
            'email' => 'SAMI.JABER@EXAMPLE.COM',
            'phone' => '+966501112233',
            'country_code' => 'sa',
        ]);

        $this->assertInstanceOf(ContactContract::class, $contact);
        $this->assertSame('Sami Ahmed Al-Jaber', $contact->name);
        $this->assertSame('sami.jaber@example.com', $contact->email);
        $this->assertSame('SA', $contact->country_code);
        $this->assertTrue($contact->is_active);

        Event::assertDispatched(ContactCreated::class, function (ContactCreated $event) use ($contact) {
            return $event->contact->id === $contact->id
                && $event->contact->name === 'Sami Ahmed Al-Jaber';
        });
    }

    public function test_create_organization_contact_persists_and_dispatches_created_event(): void
    {
        Event::fake([ContactCreated::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'National Telecom Corp',
            'tax_number' => '310000000000003',
            'email' => 'INFO@TELECOM.SA',
            'website' => 'https://telecom.sa',
            'country_code' => 'sa',
        ]);

        $this->assertSame('National Telecom Corp', $contact->name);
        $this->assertSame('info@telecom.sa', $contact->email);
        $this->assertSame('310000000000003', $contact->tax_number);

        Event::assertDispatched(ContactCreated::class, function (ContactCreated $event) use ($contact) {
            return $event->contact->id === $contact->id
                && $event->contact->isOrganization();
        });
    }

    public function test_create_domain_validation_failure_throws_exception_and_prevents_event(): void
    {
        Event::fake([ContactCreated::class]);

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->repository->create([
                'type' => 'invalid_party_type',
                'name' => 'Broken Record',
            ]);
        } finally {
            Event::assertNotDispatched(ContactCreated::class);
        }
    }

    public function test_partial_update_preserves_omitted_attributes(): void
    {
        Event::fake([ContactUpdated::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Nawaf',
            'last_name' => 'Al-Temyat',
            'email' => 'nawaf@example.com',
            'phone' => '+966500001122',
            'city' => 'Riyadh',
        ]);

        $updated = $this->repository->update([
            'phone' => '+966500009988',
        ], $contact->id);

        $this->assertSame('+966500009988', $updated->phone);
        $this->assertSame('Nawaf', $updated->first_name);
        $this->assertSame('Al-Temyat', $updated->last_name);
        $this->assertSame('nawaf@example.com', $updated->email);
        $this->assertSame('Riyadh', $updated->city);

        Event::assertDispatched(ContactUpdated::class, function (ContactUpdated $event) use ($contact) {
            return $event->contact->id === $contact->id
                && array_key_exists('phone', $event->changes);
        });
    }

    public function test_update_recalculates_canonical_name_on_identity_change(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Majed',
            'last_name' => 'Abdullah',
        ]);

        $this->assertSame('Majed Abdullah', $contact->name);

        $updated = $this->repository->update([
            'last_name' => 'Ahmed',
        ], $contact->id);

        $this->assertSame('Majed Ahmed', $updated->name);
    }

    public function test_noop_update_does_not_dispatch_updated_event(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Yasser',
            'last_name' => 'Al-Qahtani',
            'phone' => '+966551234567',
        ]);

        Event::fake([ContactUpdated::class]);

        $this->repository->update([
            'phone' => '+966551234567', // same value
        ], $contact->id);

        Event::assertNotDispatched(ContactUpdated::class);
    }

    public function test_type_transition_from_person_to_organization_clears_person_fields(): void
    {
        $person = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Saleh',
            'middle_name' => 'Ali',
            'last_name' => 'Al-Shehri',
            'job_title' => 'Managing Partner',
            'email' => 'saleh@example.com',
        ]);

        $org = $this->repository->update([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Al-Shehri Holdings LLC',
            'tax_number' => '300099988877703',
        ], $person->id);

        $this->assertSame(Contact::TYPE_ORGANIZATION, $org->type);
        $this->assertSame('Al-Shehri Holdings LLC', $org->name);
        $this->assertSame('Al-Shehri Holdings LLC', $org->organization_name);
        $this->assertSame('300099988877703', $org->tax_number);

        // Person-specific fields must be cleared
        $this->assertNull($org->first_name);
        $this->assertNull($org->middle_name);
        $this->assertNull($org->last_name);
        $this->assertNull($org->job_title);
    }

    public function test_type_transition_from_organization_to_person_clears_organization_fields(): void
    {
        $org = $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Modern Logistics Co',
            'tax_number' => '300011122233303',
        ]);

        $person = $this->repository->update([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Ziyad',
            'last_name' => 'Al-Harbi',
        ], $org->id);

        $this->assertSame(Contact::TYPE_PERSON, $person->type);
        $this->assertSame('Ziyad Al-Harbi', $person->name);
        $this->assertSame('Ziyad', $person->first_name);
        $this->assertSame('Al-Harbi', $person->last_name);

        // Organization-specific fields must be cleared
        $this->assertNull($person->organization_name);
        $this->assertNull($person->tax_number);
    }

    public function test_activation_and_deactivation_via_update(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Bandar',
            'last_name' => 'Al-Otaibi',
        ]);

        $this->assertTrue($contact->is_active);

        // Deactivate
        $deactivated = $this->repository->update(['is_active' => false], $contact->id);
        $this->assertFalse($deactivated->is_active);

        // Reactivate
        $reactivated = $this->repository->update(['is_active' => true], $contact->id);
        $this->assertTrue($reactivated->is_active);
    }

    public function test_delete_contact_persists_and_dispatches_deleted_event_with_snapshot(): void
    {
        Event::fake([ContactDeleted::class]);

        $contact = $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Temporary Partner Corp',
            'email' => 'temp@partner.com',
            'phone' => '+966119998888',
        ]);

        $contactId = (int) $contact->id;

        $deleted = $this->repository->delete($contactId);
        $this->assertTrue($deleted);

        $this->assertNull($this->repository->find($contactId));

        Event::assertDispatched(ContactDeleted::class, function (ContactDeleted $event) use ($contactId) {
            return $event->contactId === $contactId
                && $event->snapshot['name'] === 'Temporary Partner Corp'
                && $event->snapshot['type'] === Contact::TYPE_ORGANIZATION
                && $event->snapshot['email'] === 'temp@partner.com';
        });
    }

    public function test_repository_query_helpers_find_active_and_find_by_type(): void
    {
        $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Active',
            'last_name' => 'Person',
            'is_active' => true,
        ]);

        $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Inactive',
            'last_name' => 'Person',
            'is_active' => false,
        ]);

        $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Active Org',
            'is_active' => true,
        ]);

        $activeContacts = $this->repository->findActive();
        foreach ($activeContacts as $contact) {
            $this->assertTrue((bool) $contact->is_active);
        }

        $orgContacts = $this->repository->findByType(Contact::TYPE_ORGANIZATION);
        foreach ($orgContacts as $contact) {
            $this->assertSame(Contact::TYPE_ORGANIZATION, $contact->type);
        }
    }

    public function test_runtime_event_listener_captures_contact_events_without_framework_pollution(): void
    {
        $receivedCreated = null;
        $receivedUpdated = null;
        $receivedDeleted = null;

        Event::listen(ContactCreated::class, function (ContactCreated $event) use (&$receivedCreated) {
            $receivedCreated = $event;
        });

        Event::listen(ContactUpdated::class, function (ContactUpdated $event) use (&$receivedUpdated) {
            $receivedUpdated = $event;
        });

        Event::listen(ContactDeleted::class, function (ContactDeleted $event) use (&$receivedDeleted) {
            $receivedDeleted = $event;
        });

        // 1. Create
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Listener',
            'last_name' => 'Tester',
        ]);
        $this->assertNotNull($receivedCreated);
        $this->assertSame($contact->id, $receivedCreated->contact->id);

        // 2. Update
        $this->repository->update(['phone' => '+966500000099'], $contact->id);
        $this->assertNotNull($receivedUpdated);
        $this->assertSame($contact->id, $receivedUpdated->contact->id);

        // 3. Delete
        $contactId = (int) $contact->id;
        $this->repository->delete($contactId);
        $this->assertNotNull($receivedDeleted);
        $this->assertSame($contactId, $receivedDeleted->contactId);
    }

    public function test_primary_key_compatibility_with_foundation_unsigned_int(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'PK',
            'last_name' => 'Tester',
        ]);

        $this->assertIsInt($contact->id);
        $this->assertGreaterThan(0, $contact->id);
    }

    public function test_hardened_boolean_representations(): void
    {
        $truthy = [true, 1, '1', 'true', 'yes', 'on'];
        foreach ($truthy as $val) {
            $norm = $this->repository->normalizeAttributes(['is_active' => $val]);
            $this->assertTrue($norm['is_active'], "Failed asserting truthy representation for: " . var_export($val, true));
        }

        $falsy = [false, 0, '0', 'false', 'no', 'off'];
        foreach ($falsy as $val) {
            $norm = $this->repository->normalizeAttributes(['is_active' => $val]);
            $this->assertFalse($norm['is_active'], "Failed asserting falsy representation for: " . var_export($val, true));
        }
    }

    public function test_invalid_boolean_representation_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->normalizeAttributes(['is_active' => 'unrecognized_bool_value']);
    }

    public function test_normalization_precedes_validation_allowing_clean_input(): void
    {
        // Un-trimmed country_code " sa " and uppercase email " ADMIN@CORP.SA " should normalize and pass
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => '  Normalized  ',
            'last_name' => '  User  ',
            'email' => '  ADMIN@CORP.SA  ',
            'country_code' => ' sa ',
            'phone' => '  +966501112233  ',
        ]);

        $this->assertSame('SA', $contact->country_code);
        $this->assertSame('admin@corp.sa', $contact->email);
        $this->assertSame('Normalized User', $contact->name);
    }

    public function test_canonical_name_handles_unicode_and_arabic_names(): void
    {
        $person = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'محمد',
            'middle_name' => 'بن علي',
            'last_name' => 'الغامدي',
        ]);
        $this->assertSame('محمد بن علي الغامدي', $person->name);

        $org = $this->repository->create([
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'شركة علم لأمن المعلومات',
        ]);
        $this->assertSame('شركة علم لأمن المعلومات', $org->name);
    }

    public function test_canonical_name_single_component_and_whitespace_rejection(): void
    {
        $singleFirst = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'طارق',
        ]);
        $this->assertSame('طارق', $singleFirst);

        $singleLast = $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'last_name' => 'العتيبي',
        ]);
        $this->assertSame('العتيبي', $singleLast);

        $this->expectException(InvalidArgumentException::class);
        $this->repository->deriveCanonicalName([
            'type' => Contact::TYPE_PERSON,
            'name' => '     ',
        ]);
    }

    public function test_create_rollback_prevents_contact_created_event(): void
    {
        Event::fake([ContactCreated::class]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                $this->repository->create([
                    'type' => Contact::TYPE_PERSON,
                    'first_name' => 'Rollback',
                    'last_name' => 'Person',
                ]);

                throw new Exception('Simulated database/application abort inside transaction');
            });
        } catch (Exception $e) {
            // Expected
        }

        Event::assertNotDispatched(ContactCreated::class);
    }

    public function test_update_rollback_prevents_contact_updated_event(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Committed',
            'last_name' => 'User',
        ]);

        Event::fake([ContactUpdated::class]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($contact) {
                $this->repository->update(['phone' => '+966599999999'], $contact->id);

                throw new Exception('Simulated abort during update');
            });
        } catch (Exception $e) {
            // Expected
        }

        Event::assertNotDispatched(ContactUpdated::class);
    }

    public function test_delete_rollback_prevents_contact_deleted_event(): void
    {
        $contact = $this->repository->create([
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Survivor',
            'last_name' => 'User',
        ]);

        Event::fake([ContactDeleted::class]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($contact) {
                $this->repository->delete($contact->id);

                throw new Exception('Simulated abort during delete');
            });
        } catch (Exception $e) {
            // Expected
        }

        Event::assertNotDispatched(ContactDeleted::class);
        $this->assertNotNull($this->repository->find($contact->id));
    }
}
