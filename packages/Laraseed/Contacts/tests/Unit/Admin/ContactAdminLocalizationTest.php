<?php

namespace Laraseed\Contacts\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class ContactAdminLocalizationTest extends TestCase
{
    private array $en;
    private array $ar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->en = require __DIR__ . '/../../../src/Admin/Resources/lang/en/app.php';
        $this->ar = require __DIR__ . '/../../../src/Admin/Resources/lang/ar/app.php';
    }

    public function test_english_and_arabic_translations_have_exact_key_parity(): void
    {
        $enKeys = $this->flattenKeys($this->en);
        $arKeys = $this->flattenKeys($this->ar);

        sort($enKeys);
        sort($arKeys);

        $this->assertSame($enKeys, $arKeys, 'Arabic translations must have exact key parity with English translations.');
    }

    public function test_all_translations_contain_non_empty_values(): void
    {
        $this->assertNotEmptyRecursively($this->en, 'en');
        $this->assertNotEmptyRecursively($this->ar, 'ar');
    }

    public function test_required_translation_sections_exist(): void
    {
        $this->assertArrayHasKey('admin', $this->en);
        $this->assertArrayHasKey('menu', $this->en);
        $this->assertArrayHasKey('acl', $this->en);

        $this->assertArrayHasKey('fields', $this->en['admin']);
        $this->assertArrayHasKey('person', $this->en['admin']['fields']);
        $this->assertArrayHasKey('organization', $this->en['admin']['fields']);
    }

    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }
        return $keys;
    }

    private function assertNotEmptyRecursively(array $array, string $locale, string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $this->assertNotEmptyRecursively($value, $locale, $path);
            } else {
                $this->assertIsString($value, "Key [{$path}] in locale [{$locale}] must be a string.");
                $this->assertNotEmpty(trim($value), "Key [{$path}] in locale [{$locale}] must not be empty.");
            }
        }
    }
}
