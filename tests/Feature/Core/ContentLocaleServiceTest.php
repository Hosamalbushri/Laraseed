<?php

namespace Tests\Feature\Core;

use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Services\ContentLocaleService;

class ContentLocaleServiceTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.content_locale_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('content_locale_test');

        (require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000000_create_locales_table.php'))->up();
        (require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000001_create_content_locale_settings_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('content_locale_test');

        parent::tearDown();
    }

    public function test_primary_is_independent_of_admin_locale_and_exactly_one(): void
    {
        config()->set('app.locale', 'ar');
        config()->set('app.available_locales', ['ar' => 'Arabic']);
        $service = app(ContentLocaleService::class);

        $this->assertSame('en', $service->primaryContentLocale()->code);
        $this->assertSame(1, DB::table('content_locale_settings')->count());
        $this->assertSame(['ar', 'en'], $service->activeContentLocales()->pluck('code')->all());

        $service->changePrimary(Locale::where('code', 'ar')->value('id'));
        $this->assertSame('ar', $service->primaryContentLocale()->code);
        $this->assertSame(1, DB::table('content_locale_settings')->count());
        $this->assertSame('ar', config('app.locale'));
        $this->assertSame(['ar' => 'Arabic'], config('app.available_locales'));
    }

    public function test_database_rejects_second_primary_setting(): void
    {
        $this->expectException(QueryException::class);
        DB::table('content_locale_settings')->insert([
            'key' => 'primary',
            'primary_locale_id' => Locale::where('code', 'ar')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_another_setting_key(): void
    {
        $this->expectException(QueryException::class);
        DB::table('content_locale_settings')->insert([
            'key' => 'secondary',
            'primary_locale_id' => Locale::where('code', 'ar')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_inactive_primary_switch_activates_atomically_and_keeps_old_active(): void
    {
        $service = app(ContentLocaleService::class);
        $french = $service->create(['code' => 'fr', 'name' => 'French', 'direction' => 'ltr', 'is_active' => false, 'sort_order' => 3]);

        $service->changePrimary($french->id);

        $this->assertSame('fr', $service->primaryContentLocale()->code);
        $this->assertTrue($french->fresh()->is_active);
        $this->assertTrue(Locale::where('code', 'en')->firstOrFail()->is_active);
    }

    public function test_primary_deactivation_and_generic_activation_are_blocked(): void
    {
        $service = app(ContentLocaleService::class);
        $english = $service->primaryContentLocale();

        try {
            $service->deactivate($english->id);
            $this->fail('Primary locale was deactivated.');
        } catch (DomainException) {
            $this->assertTrue($english->fresh()->is_active);
        }

        $this->expectException(LogicException::class);
        $english->update(['is_active' => false]);
    }

    public function test_nonprimary_deactivation_keeps_identity_and_primary(): void
    {
        $service = app(ContentLocaleService::class);
        $arabic = Locale::where('code', 'ar')->firstOrFail();
        $service->deactivate($arabic->id);

        $this->assertFalse($arabic->fresh()->is_active);
        $this->assertSame('en', $service->primaryContentLocale()->code);
        $this->assertNotNull(app(LocaleRepository::class)->findByCode('ar'));

        try {
            $service->deactivate($service->primaryContentLocale()->id);
            $this->fail('The last active locale was deactivated.');
        } catch (DomainException) {
            $this->assertSame(['en'], $service->activeContentLocales()->pluck('code')->all());
        }
    }

    public function test_failed_primary_switch_rolls_back_activation(): void
    {
        $service = app(ContentLocaleService::class);
        $french = $service->create(['code' => 'fr', 'name' => 'French', 'direction' => 'ltr', 'is_active' => false, 'sort_order' => 3]);
        DB::statement("CREATE TRIGGER abort_primary_change BEFORE UPDATE ON content_locale_settings BEGIN SELECT RAISE(ABORT, 'blocked'); END");

        try {
            $service->changePrimary($french->id);
            $this->fail('Switch should have failed.');
        } catch (QueryException) {
            $this->assertFalse($french->fresh()->is_active);
            $this->assertSame('en', $service->primaryContentLocale()->code);
        }
    }

    public function test_metadata_cannot_change_code_or_activity(): void
    {
        $service = app(ContentLocaleService::class);
        $english = $service->primaryContentLocale();
        $service->updateMetadata($english->id, ['name' => 'English content', 'direction' => 'ltr', 'sort_order' => 2]);
        $this->assertSame('English content', $english->fresh()->name);

        $this->expectException(\InvalidArgumentException::class);
        $service->updateMetadata($english->id, ['is_active' => false]);
    }
}
