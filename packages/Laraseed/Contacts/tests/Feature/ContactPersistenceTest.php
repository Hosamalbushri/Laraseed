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

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Tests\TestCase;

class ContactPersistenceTest extends TestCase
{
    use DatabaseTransactions;

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
    }

    public function test_contacts_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('contacts'));

        $expectedColumns = [
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
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('contacts', $column),
                "Failed asserting that contacts table contains column [{$column}]."
            );
        }
    }

    public function test_contacts_table_has_no_forbidden_residue_columns(): void
    {
        $forbiddenColumns = [
            'deleted_at',
            'is_customer',
            'is_supplier',
            'is_employee',
            'is_lead',
            'user_id',
            'created_by',
            'owner_id',
            'company_id',
            'tenant_id',
        ];

        foreach ($forbiddenColumns as $column) {
            $this->assertFalse(
                Schema::hasColumn('contacts', $column),
                "Forbidden column [{$column}] detected in contacts table."
            );
        }
    }

    public function test_persistence_round_trip_for_person_contact(): void
    {
        $person = Contact::create([
            'type' => Contact::TYPE_PERSON,
            'name' => 'Dr. Ahmed Ali',
            'first_name' => 'Ahmed',
            'middle_name' => 'Hassan',
            'last_name' => 'Ali',
            'job_title' => 'Chief Technology Officer',
            'organization_name' => 'Acme Labs',
            'email' => 'ahmed.ali@example.com',
            'phone' => '+966500000001',
            'mobile' => '+966550000002',
            'website' => 'https://ahmedali.dev',
            'address_line_1' => 'King Fahd Road',
            'address_line_2' => 'Building 4, Suite 102',
            'city' => 'Riyadh',
            'state' => 'Riyadh Province',
            'postal_code' => '12211',
            'country_code' => 'SA',
            'notes' => 'Key executive contact.',
            'is_active' => true,
        ]);

        $this->assertNotNull($person->id);
        $this->assertSame(Contact::TYPE_PERSON, $person->type);
        $this->assertTrue($person->isPerson());
        $this->assertFalse($person->isOrganization());

        $retrieved = Contact::find($person->id);
        $this->assertNotNull($retrieved);
        $this->assertSame('Dr. Ahmed Ali', $retrieved->name);
        $this->assertSame('Ahmed', $retrieved->first_name);
        $this->assertSame('Hassan', $retrieved->middle_name);
        $this->assertSame('Ali', $retrieved->last_name);
        $this->assertSame('Chief Technology Officer', $retrieved->job_title);
        $this->assertSame('Acme Labs', $retrieved->organization_name);
        $this->assertSame('ahmed.ali@example.com', $retrieved->email);
        $this->assertSame('+966500000001', $retrieved->phone);
        $this->assertSame('SA', $retrieved->country_code);
        $this->assertTrue($retrieved->is_active);
    }

    public function test_persistence_round_trip_for_organization_contact(): void
    {
        $org = Contact::create([
            'type' => Contact::TYPE_ORGANIZATION,
            'name' => 'Arabian Technology Solutions LLC',
            'organization_name' => 'Arabian Technology Solutions LLC',
            'tax_number' => '300000000000003',
            'email' => 'info@arabian-tech.sa',
            'phone' => '+966112345678',
            'website' => 'https://arabian-tech.sa',
            'city' => 'Riyadh',
            'country_code' => 'SA',
            'is_active' => false,
        ]);

        $this->assertNotNull($org->id);
        $this->assertSame(Contact::TYPE_ORGANIZATION, $org->type);
        $this->assertTrue($org->isOrganization());
        $this->assertFalse($org->isPerson());

        $retrieved = Contact::find($org->id);
        $this->assertNotNull($retrieved);
        $this->assertSame('Arabian Technology Solutions LLC', $retrieved->name);
        $this->assertSame('300000000000003', $retrieved->tax_number);
        $this->assertFalse($retrieved->is_active);
        $this->assertNull($retrieved->first_name);
        $this->assertNull($retrieved->last_name);
    }

    public function test_nullable_secondary_fields_allow_minimal_contact_creation(): void
    {
        $minimal = Contact::create([
            'type' => Contact::TYPE_PERSON,
            'name' => 'Minimal Contact',
        ]);

        $this->assertNotNull($minimal->id);
        $this->assertSame('Minimal Contact', $minimal->name);
        $this->assertTrue($minimal->is_active); // default is true
        $this->assertNull($minimal->email);
        $this->assertNull($minimal->phone);
        $this->assertNull($minimal->country_code);
    }

    public function test_invalid_type_boundary_behavior(): void
    {
        // Persistence layer accepts string value; Application layer (Step 04) will enforce 'in:person,organization'
        $custom = Contact::create([
            'type' => 'custom_type',
            'name' => 'Custom Type Party',
        ]);

        $this->assertSame('custom_type', $custom->type);
        $this->assertFalse($custom->isPerson());
        $this->assertFalse($custom->isOrganization());
    }
}
