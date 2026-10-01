<?php

namespace Laraseed\Contacts\Tests\Unit;

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

use Laraseed\Contacts\Contracts\Contact as ContactContract;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Models\ContactProxy;
use Laraseed\Contacts\Tests\TestCase;

class ContactModelTest extends TestCase
{
    public function test_contact_implements_canonical_contract(): void
    {
        $contact = new Contact();

        $this->assertInstanceOf(ContactContract::class, $contact);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $contact);
    }

    public function test_contact_constants_and_table_are_defined(): void
    {
        $contact = new Contact();

        $this->assertSame('contacts', $contact->getTable());
        $this->assertSame('person', Contact::TYPE_PERSON);
        $this->assertSame('organization', Contact::TYPE_ORGANIZATION);
    }

    public function test_type_helpers_evaluate_correctly(): void
    {
        $person = new Contact(['type' => Contact::TYPE_PERSON]);
        $this->assertTrue($person->isPerson());
        $this->assertFalse($person->isOrganization());

        $org = new Contact(['type' => Contact::TYPE_ORGANIZATION]);
        $this->assertTrue($org->isOrganization());
        $this->assertFalse($org->isPerson());

        $custom = new Contact(['type' => 'other']);
        $this->assertFalse($custom->isPerson());
        $this->assertFalse($custom->isOrganization());
    }

    public function test_mass_assignment_fillable_whitelist(): void
    {
        $contact = new Contact();

        $expectedFillable = [
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
        ];

        $this->assertSame($expectedFillable, $contact->getFillable());
        $this->assertNotContains('id', $contact->getFillable());
        $this->assertNotContains('created_at', $contact->getFillable());
        $this->assertNotContains('updated_at', $contact->getFillable());
    }

    public function test_casts_definition(): void
    {
        $contact = new Contact(['is_active' => 1]);

        $this->assertTrue($contact->is_active);
        $this->assertTrue($contact->hasCast('is_active', 'boolean'));
        $this->assertSame('boolean', $contact->getCasts()['is_active']);
    }

    public function test_contact_proxy_resolves_contact_model(): void
    {
        $this->assertTrue(class_exists(ContactProxy::class));
        $this->assertTrue(is_subclass_of(ContactProxy::class, \Konekt\Concord\Proxies\ModelProxy::class));
    }
}
