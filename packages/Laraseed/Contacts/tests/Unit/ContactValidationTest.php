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

use Illuminate\Support\Facades\Validator;
use Laraseed\Contacts\Http\Requests\StoreContactRequest;
use Laraseed\Contacts\Http\Requests\UpdateContactRequest;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Tests\TestCase;

class ContactValidationTest extends TestCase
{
    public function test_valid_person_payload_passes_store_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => Contact::TYPE_PERSON,
            'first_name' => 'Faisal',
            'last_name' => 'Al-Harbi',
            'email' => 'faisal@example.com',
            'phone' => '+966501234567',
            'country_code' => 'SA',
            'is_active' => true,
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_valid_organization_payload_passes_store_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Saudi Tech LLC',
            'tax_number' => '300123456789003',
            'website' => 'https://sauditech.example.com',
            'country_code' => 'SA',
            'is_active' => true,
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_invalid_type_fails_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => 'alien_type',
            'name' => 'Invalid Type Party',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_invalid_email_format_fails_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => Contact::TYPE_PERSON,
            'name' => 'John Doe',
            'email' => 'not-a-valid-email',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_invalid_country_code_length_fails_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => Contact::TYPE_PERSON,
            'name' => 'John Doe',
            'country_code' => 'SAU', // 3 chars instead of 2
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('country_code', $validator->errors()->toArray());
    }

    public function test_invalid_website_fails_validation(): void
    {
        $rules = (new StoreContactRequest())->rules();

        $data = [
            'type' => Contact::TYPE_ORGANIZATION,
            'organization_name' => 'Tech Corp',
            'website' => 'htt//invalid-url',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('website', $validator->errors()->toArray());
    }

    public function test_update_request_allows_partial_attributes(): void
    {
        $rules = (new UpdateContactRequest())->rules();

        $data = [
            'phone' => '+966555555555',
            'is_active' => false,
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }
}
