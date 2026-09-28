<?php

namespace Tests\Feature\Core;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ValueError;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Enums\LocaleDirection;
use Webkul\Core\Models\Locale;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleFoundationTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.locale_foundation_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('locale_foundation_test');

        $migration = require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000000_create_locales_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('locale_foundation_test');

        parent::tearDown();
    }

    public function test_migration_creates_schema_and_bootstraps_configured_locale_snapshot(): void
    {
        $this->assertTrue(Schema::hasTable('locales'));
        foreach (['id', 'code', 'name', 'direction', 'is_active', 'sort_order', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('locales', $column), "Missing {$column}");
        }

        $this->assertSame(['ar', 'en'], DB::table('locales')->orderBy('sort_order')->pluck('code')->all());
        $this->assertSame(['Arabic', 'English'], DB::table('locales')->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame('rtl', DB::table('locales')->where('code', 'ar')->value('direction'));
        $this->assertSame('ltr', DB::table('locales')->where('code', 'en')->value('direction'));
        $this->assertNotNull(DB::table('locales')->where('code', 'en')->value('created_at'));
    }

    public function test_bootstrap_snapshot_does_not_change_when_config_changes_later(): void
    {
        config()->set('app.available_locales', ['fr' => 'French']);
        $migration = require base_path('packages/Webkul/Core/src/Database/Migrations/2026_09_28_000000_create_locales_table.php');

        $migration->down();
        $migration->up();

        $this->assertSame(['ar', 'en'], DB::table('locales')->orderBy('sort_order')->pluck('code')->all());
    }

    public function test_concord_contract_proxy_model_and_casts(): void
    {
        $this->assertSame(Locale::class, LocaleProxy::modelClass());
        $locale = Locale::query()->where('code', 'ar')->firstOrFail();
        $this->assertInstanceOf(LocaleContract::class, $locale);
        $this->assertSame('ar', $locale->code());
        $this->assertSame(LocaleDirection::RTL, $locale->direction());
        $this->assertTrue($locale->isActive());
        $this->assertIsInt($locale->sort_order);
    }

    public function test_code_normalization_and_uniqueness_prevent_case_collisions(): void
    {
        $this->assertSame('en', Locale::normalizeCode('EN'));
        $this->assertSame('pt_BR', Locale::normalizeCode('PT-br'));
        $this->assertSame('ar', Locale::normalizeCode('ar'));

        $locale = Locale::create([
            'code' => 'PT-br',
            'name' => 'Portuguese (Brazil)',
            'direction' => LocaleDirection::LTR,
        ]);
        $this->assertSame('pt_BR', $locale->code);

        $this->expectException(QueryException::class);
        Locale::create([
            'code' => 'AR',
            'name' => 'Duplicate Arabic',
            'direction' => LocaleDirection::RTL,
        ]);
    }

    public function test_invalid_codes_and_directions_are_rejected_at_model_boundary(): void
    {
        foreach (['', 'e', 'english', 'en_US_extra', 'en/US', 'en US', 'éñ'] as $code) {
            try {
                Locale::normalizeCode($code);
                $this->fail("Accepted invalid code {$code}");
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(ValueError::class);
        Locale::create([
            'code' => 'fr',
            'name' => 'French',
            'direction' => 'upwards',
        ]);
    }

    public function test_name_and_sort_order_are_validated_at_model_boundary(): void
    {
        $locale = new Locale;

        try {
            $locale->name = '  ';
            $this->fail('Blank locale name was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $locale->sort_order = -1;
    }

    public function test_database_rejects_arbitrary_direction(): void
    {
        $this->expectException(QueryException::class);
        DB::table('locales')->insert([
            'code' => 'fr',
            'name' => 'French',
            'direction' => 'up',
            'is_active' => true,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_repository_resolves_active_order_inactive_identity_and_direction(): void
    {
        $repository = app(LocaleRepository::class);
        $this->assertSame(['ar', 'en'], $repository->activeOrdered()->pluck('code')->all());
        $this->assertSame(LocaleDirection::RTL, $repository->directionFor('AR'));
        $this->assertSame(LocaleDirection::LTR, $repository->directionFor('en'));
        $this->assertTrue($repository->isActiveCode('en'));

        $english = $repository->findByCode('EN');
        // This repository-only fixture predates the protected Step 02 lifecycle.
        DB::table('locales')->where('id', $english->id)->update(['is_active' => false]);

        $this->assertFalse($repository->isActiveCode('en'));
        $this->assertNotNull($repository->findByCode('en'));
        $this->assertSame(['ar'], $repository->activeOrdered()->pluck('code')->all());
        $this->assertSame(LocaleDirection::LTR, $repository->directionFor('en'));
    }

    public function test_dynamic_locale_addition_needs_no_config_change(): void
    {
        $repository = app(LocaleRepository::class);
        $this->assertArrayNotHasKey('fr', config('app.available_locales'));

        Locale::create([
            'code' => 'FR',
            'name' => 'Français',
            'direction' => LocaleDirection::LTR,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->assertTrue($repository->isActiveCode('fr'));
        $this->assertSame(['ar', 'fr', 'en'], $repository->activeOrdered()->pluck('code')->all());
    }

    public function test_persisted_code_is_immutable_and_identity_cannot_be_deleted(): void
    {
        $locale = Locale::query()->where('code', 'en')->firstOrFail();
        $locale->code = 'fr';

        try {
            $locale->save();
            $this->fail('Persisted locale code changed.');
        } catch (LogicException) {
            $this->assertSame('en', $locale->fresh()->code);
        }

        $this->expectException(LogicException::class);
        app(LocaleRepository::class)->delete($locale->id);
    }

    public function test_model_hard_delete_is_rejected(): void
    {
        $locale = Locale::query()->where('code', 'en')->firstOrFail();

        $this->expectException(LogicException::class);
        $locale->delete();
    }

    public function test_existing_default_and_fallback_codes_exist_in_registry(): void
    {
        $repository = app(LocaleRepository::class);

        $this->assertTrue($repository->isActiveCode('ar'));
        $this->assertTrue($repository->isActiveCode(config('app.fallback_locale')));
    }

    public function test_core_locale_selector_is_unchanged_and_boot_before_migration_is_safe(): void
    {
        $this->assertSame([
            ['title' => 'Arabic', 'value' => 'ar'],
            ['title' => 'English', 'value' => 'en'],
        ], core()->locales());

        $process = new Process(['php', 'artisan', 'about', '--only=environment'], base_path(), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);
        $process->mustRun();
        $this->assertStringContainsString('Environment', $process->getOutput());
    }
}
