<?php

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Core\Actions\CreateLocaleAction;
use Webkul\Core\Contracts\ContentLocaleManager;
use Webkul\Core\Enums\LocaleDirection;
use Webkul\Core\Models\Locale;
use Webkul\Core\Services\ContentLocaleService;

class ContentLocaleManagerTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.content_locale_manager_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('content_locale_manager_test');

        (require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000000_create_locales_table.php'))->up();
        (require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000001_create_content_locale_settings_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('content_locale_manager_test');

        parent::tearDown();
    }

    public function test_contract_is_bound_to_service_in_container(): void
    {
        $manager = app(ContentLocaleManager::class);

        $this->assertInstanceOf(ContentLocaleService::class, $manager);
    }

    public function test_create_locale_normalizes_code_and_defaults_to_inactive(): void
    {
        $manager = app(ContentLocaleManager::class);

        $locale = $manager->createLocale([
            'code' => 'PT-br',
            'name' => 'Portuguese (Brazil)',
            'direction' => 'ltr',
            'sort_order' => 5,
        ]);

        $this->assertInstanceOf(Locale::class, $locale);
        $this->assertSame('pt_BR', $locale->code);
        $this->assertSame('Portuguese (Brazil)', $locale->name);
        $this->assertSame(LocaleDirection::LTR, $locale->direction);
        $this->assertSame(5, $locale->sort_order);
        $this->assertFalse($locale->is_active);
    }

    public function test_create_locale_action_executes_via_domain_action(): void
    {
        $action = app(CreateLocaleAction::class);

        $locale = $action->execute([
            'code' => 'es',
            'name' => 'Spanish',
            'direction' => LocaleDirection::LTR,
            'sort_order' => 10,
        ]);

        $this->assertSame('es', $locale->code);
        $this->assertFalse($locale->is_active);
    }

    public function test_create_locale_rejects_duplicate_code(): void
    {
        $manager = app(ContentLocaleManager::class);

        $this->expectException(ValidationException::class);

        $manager->createLocale([
            'code' => 'EN',
            'name' => 'English Duplicate',
            'direction' => 'ltr',
            'sort_order' => 1,
        ]);
    }

    public function test_create_locale_rejects_invalid_code_format(): void
    {
        $manager = app(ContentLocaleManager::class);

        $this->expectException(ValidationException::class);

        $manager->createLocale([
            'code' => '123_invalid',
            'name' => 'Invalid Code',
            'direction' => 'ltr',
            'sort_order' => 1,
        ]);
    }

    public function test_create_locale_rejects_invalid_direction(): void
    {
        $manager = app(ContentLocaleManager::class);

        $this->expectException(ValidationException::class);

        $manager->createLocale([
            'code' => 'de',
            'name' => 'German',
            'direction' => 'diagonal',
            'sort_order' => 1,
        ]);
    }

    public function test_create_locale_rejects_negative_sort_order(): void
    {
        $manager = app(ContentLocaleManager::class);

        $this->expectException(ValidationException::class);

        $manager->createLocale([
            'code' => 'de',
            'name' => 'German',
            'direction' => 'ltr',
            'sort_order' => -5,
        ]);
    }

    public function test_update_metadata_validates_and_persists_changes(): void
    {
        $manager = app(ContentLocaleManager::class);
        $english = $manager->primaryContentLocale();

        $updated = $manager->updateMetadata($english->id, [
            'name' => 'Global English',
            'direction' => 'ltr',
            'sort_order' => 3,
        ]);

        $this->assertSame('Global English', $updated->name);
        $this->assertSame(3, $updated->sort_order);
    }

    public function test_update_metadata_rejects_invalid_values(): void
    {
        $manager = app(ContentLocaleManager::class);
        $english = $manager->primaryContentLocale();

        $this->expectException(ValidationException::class);

        $manager->updateMetadata($english->id, [
            'name' => '',
            'direction' => 'invalid_dir',
            'sort_order' => -1,
        ]);
    }

    public function test_find_locale_retrieves_model_by_id(): void
    {
        $manager = app(ContentLocaleManager::class);
        $english = $manager->primaryContentLocale();

        $found = $manager->findLocale($english->id);

        $this->assertSame($english->id, $found->id);
        $this->assertSame($english->code, $found->code);
    }
}
