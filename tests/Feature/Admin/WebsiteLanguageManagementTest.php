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
        session()->forget('url.intended');
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
}
