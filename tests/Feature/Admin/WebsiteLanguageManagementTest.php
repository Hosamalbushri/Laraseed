<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\Settings\WebsiteLanguageController;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class WebsiteLanguageManagementTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.website_language_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('website_language_test');
        Artisan::call('migrate', ['--database' => 'website_language_test', '--force' => true]);
    }

    protected function tearDown(): void
    {
        auth()->guard('user')->logout();
        session()->flush();
        $this->flushSession();
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('website_language_test');

        parent::tearDown();
    }

    private function user(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Website locale test '.uniqid(),
            'permission_type' => 'custom',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Website locale tester',
            'email' => uniqid('website-locale-').'@example.test',
            'password' => Hash::make('password'),
            'status' => true,
            'role_id' => $role->id,
            'view_permission' => 'global',
        ]);
    }

    public function test_view_permission_does_not_grant_mutations(): void
    {
        $this->actingAs($this->user(['settings', 'settings.website_languages', 'settings.website_languages.overview']), 'user');
        $this->assertSame('settings.website_languages.overview', acl()->getRoles()['admin.settings.website-languages.index']);
        $view = app(WebsiteLanguageController::class)->index();
        $this->assertSame('admin::settings.website-languages.index', $view->name());
        $this->assertSame('en', $view->getData()['primary']->code);

        $this->post(route('admin.settings.website-languages.store'), [
            'code' => 'fr', 'name' => 'French', 'direction' => 'ltr', 'sort_order' => 2,
        ])->assertUnauthorized();

        $this->assertFalse(Locale::where('code', 'fr')->exists());
    }

    public function test_management_actions_are_acl_scoped_and_preserve_admin_locale(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.create', 'settings.website_languages.edit',
            'settings.website_languages.manage',
        ]), 'user');
        $this->post(route('admin.settings.website-languages.store'), [
            'code' => 'fr', 'name' => 'French', 'direction' => 'ltr', 'sort_order' => 2,
        ])->assertRedirect();
        $french = Locale::where('code', 'fr')->firstOrFail();
        $this->assertFalse($french->is_active);
        $adminLocale = config('app.locale');

        $this->post(route('admin.settings.website-languages.primary', $french->id))
            ->assertRedirect();
        $this->assertTrue($french->fresh()->is_active);
        $this->assertSame($french->id, (int) DB::table('content_locale_settings')->value('primary_locale_id'));
        $this->assertSame($adminLocale, config('app.locale'));

        $this->post(route('admin.settings.website-languages.deactivate', $french->id))
            ->assertRedirect()->assertSessionHasErrors('language');
        $this->assertTrue($french->fresh()->is_active);
    }

    public function test_store_rejects_invalid_inputs_and_redirects_with_errors(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.create',
        ]), 'user');

        // Duplicate code
        $this->post(route('admin.settings.website-languages.store'), [
            'code' => 'EN',
            'name' => 'Duplicate English',
            'direction' => 'ltr',
            'sort_order' => 1,
        ])->assertRedirect()->assertSessionHasErrors('code');

        // Invalid code syntax
        $this->post(route('admin.settings.website-languages.store'), [
            'code' => '123_invalid',
            'name' => 'Invalid Code',
            'direction' => 'ltr',
            'sort_order' => 1,
        ])->assertRedirect()->assertSessionHasErrors('code');

        // Missing required fields
        $this->post(route('admin.settings.website-languages.store'), [
            'code' => 'de',
        ])->assertRedirect()->assertSessionHasErrors(['name', 'direction', 'sort_order']);
    }

    public function test_update_modifies_language_metadata_via_core_api(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.create', 'settings.website_languages.edit',
        ]), 'user');

        $this->post(route('admin.settings.website-languages.store'), [
            'code' => 'it',
            'name' => 'Italian',
            'direction' => 'ltr',
            'sort_order' => 10,
        ])->assertRedirect();

        $italian = Locale::where('code', 'it')->firstOrFail();

        $this->put(route('admin.settings.website-languages.update', $italian->id), [
            'name' => 'Italiano',
            'direction' => 'ltr',
            'sort_order' => 15,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Italiano', $italian->fresh()->name);
        $this->assertSame(15, $italian->fresh()->sort_order);
    }

    public function test_deactivate_last_active_locale_is_rejected_with_translated_error(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.manage',
        ]), 'user');

        // Deactivate Arabic, leaving English as the only active locale
        $arabic = Locale::where('code', 'ar')->firstOrFail();
        $this->post(route('admin.settings.website-languages.deactivate', $arabic->id))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($arabic->fresh()->is_active);

        // Attempting to deactivate English (primary) must fail with translated error
        $english = Locale::where('code', 'en')->firstOrFail();
        $this->post(route('admin.settings.website-languages.deactivate', $english->id))
            ->assertRedirect()
            ->assertSessionHasErrors(['language' => trans('admin::website-languages.cannot-deactivate-primary')]);

        $this->assertTrue($english->fresh()->is_active);
    }

    public function test_index_view_renders_standard_admin_components_and_breadcrumbs(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.create',
        ]), 'user');

        $response = $this->get(route('admin.settings.website-languages.index'));
        $response->assertOk();
        $response->assertSee(trans('admin::website-languages.title'));
        $response->assertSee(trans('admin::website-languages.add'));
        $response->assertSee(trans('admin::website-languages.code'));
        $response->assertSee(trans('admin::website-languages.direction'));
        $response->assertSee(trans('admin::website-languages.ltr'));
        $response->assertSee(trans('admin::website-languages.rtl'));
    }

    public function test_shipped_management_labels_have_matching_keys(): void
    {
        $base = base_path('packages/Webkul/Admin/src/Resources/lang');
        $reference = array_keys(require $base.'/en/website-languages.php');

        foreach (['ar', 'es', 'fa', 'pt_BR', 'tr', 'vi'] as $code) {
            $labels = require $base.'/'.$code.'/website-languages.php';
            $this->assertSame($reference, array_keys($labels), $code);

            foreach ($labels as $key => $value) {
                $this->assertNotSame('', trim($value), $code.':'.$key);
            }
        }
    }

    public function test_index_ajax_returns_datagrid_json(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.edit', 'settings.website_languages.manage',
        ]), 'user');

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.settings.website-languages.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'columns',
            'actions',
            'records',
            'meta',
        ]);

        $json = $response->json();
        $columnIndices = array_column($json['columns'], 'index');
        $this->assertContains('id', $columnIndices);
        $this->assertContains('code', $columnIndices);
        $this->assertContains('name', $columnIndices);
        $this->assertContains('direction', $columnIndices);
        $this->assertContains('is_active', $columnIndices);
        $this->assertContains('is_primary', $columnIndices);
        $this->assertContains('sort_order', $columnIndices);

        $recordCodes = array_column($json['records'], 'code');
        $this->assertContains('en', $recordCodes);
        $this->assertContains('ar', $recordCodes);
    }

    public function test_datagrid_records_contain_necessary_attributes_for_modal(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.edit',
        ]), 'user');

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.settings.website-languages.index'));

        $response->assertOk();
        $englishRecord = collect($response->json('records'))->firstWhere('code', 'en');
        $this->assertNotNull($englishRecord);
        $this->assertSame('en', $englishRecord['code']);
        $this->assertSame('English', $englishRecord['name']);
        $this->assertSame('ltr', $englishRecord['raw_direction']);
        $this->assertSame(1, $englishRecord['raw_is_active']);
        $this->assertSame(1, $englishRecord['raw_is_primary']);
    }

    public function test_ajax_store_and_update_return_json_response(): void
    {
        $this->actingAs($this->user([
            'settings', 'settings.website_languages', 'settings.website_languages.overview',
            'settings.website_languages.create', 'settings.website_languages.edit',
        ]), 'user');

        // Create via AJAX
        $createResponse = $this->postJson(route('admin.settings.website-languages.store'), [
            'code'       => 'nl',
            'name'       => 'Dutch',
            'direction'  => 'ltr',
            'sort_order' => 8,
        ]);

        $createResponse->assertOk();
        $createResponse->assertJson([
            'data' => [
                'code'       => 'nl',
                'name'       => 'Dutch',
                'direction'  => 'ltr',
                'sort_order' => 8,
                'is_active'  => false,
            ],
            'message' => trans('admin::website-languages.saved'),
        ]);

        $dutch = Locale::where('code', 'nl')->firstOrFail();

        // Update via AJAX
        $updateResponse = $this->putJson(route('admin.settings.website-languages.update', $dutch->id), [
            'name'       => 'Nederlands',
            'direction'  => 'ltr',
            'sort_order' => 9,
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJson([
            'data' => [
                'id'         => $dutch->id,
                'name'       => 'Nederlands',
                'sort_order' => 9,
            ],
            'message' => trans('admin::website-languages.saved'),
        ]);
    }
}
