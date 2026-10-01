<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\SystemConfig;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class AdminCoreConfigTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.core_config_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('core_config_test');
        Artisan::call('migrate', ['--database' => 'core_config_test', '--force' => true]);
    }

    protected function tearDown(): void
    {
        auth()->guard('user')->logout();
        session()->flush();
        $this->flushSession();
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('core_config_test');

        parent::tearDown();
    }

    private function user(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Core Config Tester Role '.uniqid(),
            'permission_type' => 'custom',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Core Config Tester',
            'email' => uniqid('core-config-').'@example.test',
            'password' => Hash::make('password'),
            'status' => true,
            'role_id' => $role->id,
            'view_permission' => 'global',
        ]);
    }

    public function test_core_config_is_registered_in_configuration_and_has_expected_hierarchy(): void
    {
        $rawConfig = config('core_config');

        $this->assertIsArray($rawConfig);
        $this->assertNotEmpty($rawConfig);

        $keys = collect($rawConfig)->pluck('key')->all();

        $this->assertContains('general', $keys);
        $this->assertContains('general.general', $keys);
        $this->assertContains('general.general.locale_settings', $keys);
        $this->assertContains('general.design', $keys);
        $this->assertContains('general.design.admin_logo', $keys);
        $this->assertContains('general.settings', $keys);
        $this->assertContains('general.settings.footer', $keys);
        $this->assertContains('general.settings.menu', $keys);
        $this->assertContains('general.settings.menu_color', $keys);
    }

    public function test_system_config_builds_structured_items_and_fields(): void
    {
        $systemConfig = app(SystemConfig::class);
        $items = $systemConfig->getItems();

        $this->assertTrue($items->isNotEmpty());

        $general = $items->where('key', 'general')->first();
        $this->assertNotNull($general);
        $this->assertSame('general', $general->getKey());

        $children = $general->getChildren();
        $this->assertTrue($children->has('general') || isset($children['general']));
        $this->assertTrue($children->has('design') || isset($children['design']));
        $this->assertTrue($children->has('settings') || isset($children['settings']));

        $brandColorField = $systemConfig->getConfigField('general.settings.menu_color.brand_color');
        $this->assertNotNull($brandColorField);
        $this->assertSame('brand_color', $brandColorField['name']);
        $this->assertSame('#0E90D9', $brandColorField['default']);
    }

    public function test_system_config_is_safe_when_core_config_is_null_or_empty(): void
    {
        Config::set('core_config', null);

        $systemConfig = app(SystemConfig::class);
        $items = $systemConfig->getItems();

        $this->assertTrue($items->isEmpty());
        $this->assertNull($systemConfig->getConfigField('nonexistent.field'));
        $this->assertNull($systemConfig->getConfigData('nonexistent.field'));
    }

    public function test_core_get_config_data_returns_default_when_not_in_db_and_db_value_when_persisted(): void
    {
        $this->assertSame('#0E90D9', core()->getConfigData('general.settings.menu_color.brand_color'));
        $this->assertSame('Dashboard', core()->getConfigData('general.settings.menu.dashboard'));

        CoreConfig::create([
            'code' => 'general.settings.menu_color.brand_color',
            'value' => '#FF5500',
        ]);

        $this->assertSame('#FF5500', core()->getConfigData('general.settings.menu_color.brand_color'));
    }

    public function test_core_config_repository_creates_and_updates_settings_without_static_accumulation(): void
    {
        $repo = app(CoreConfigRepository::class);

        $repo->create([
            'general' => [
                'settings' => [
                    'menu_color' => [
                        'brand_color' => '#112233',
                    ],
                ],
            ],
        ]);

        $this->assertSame('#112233', core()->getConfigData('general.settings.menu_color.brand_color'));

        $repo->create([
            'general' => [
                'settings' => [
                    'menu' => [
                        'dashboard' => 'Custom Dashboard',
                    ],
                ],
            ],
        ]);

        $this->assertSame('Custom Dashboard', core()->getConfigData('general.settings.menu.dashboard'));
        $this->assertSame('#112233', core()->getConfigData('general.settings.menu_color.brand_color'));
    }

    public function test_core_config_repository_search_returns_matches_and_handles_empty_query(): void
    {
        $repo = app(CoreConfigRepository::class);
        $items = system_config()->getItems();

        $emptyResults = $repo->search($items, '');
        $this->assertSame([], $emptyResults);

        $localeResults = $repo->search($items, 'لوحة الإدارة');
        $this->assertIsArray($localeResults);
    }

    public function test_unauthenticated_user_cannot_access_configuration_routes(): void
    {
        $this->get(route('admin.configuration.index'))->assertRedirect(route('admin.session.create'));
        $this->get(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'general']))->assertRedirect(route('admin.session.create'));
        $this->post(route('admin.configuration.store'))->assertRedirect(route('admin.session.create'));
        $this->get(route('admin.configuration.search'))->assertRedirect(route('admin.session.create'));
    }

    public function test_user_without_configuration_permission_is_denied(): void
    {
        $user = $this->user(['dashboard']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.configuration.index'))->assertUnauthorized();
        $this->get(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'general']))->assertUnauthorized();
        $this->post(route('admin.configuration.store'), [])->assertUnauthorized();
        $this->get(route('admin.configuration.search'))->assertUnauthorized();
    }

    public function test_user_with_configuration_permission_can_view_index_and_edit_sections(): void
    {
        $user = $this->user(['configuration']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.configuration.index'))->assertOk();
        $this->get(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'general']))->assertOk();
        $this->get(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'design']))->assertOk();
        $this->get(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'settings']))->assertOk();
    }

    public function test_invalid_configuration_slugs_return_404(): void
    {
        $user = $this->user(['configuration']);
        $this->actingAs($user, 'user');

        $this->get(route('admin.configuration.index', ['slug' => 'invalid_section', 'slug2' => 'invalid_group']))->assertNotFound();
    }

    public function test_user_with_permission_can_save_configuration_and_redirects_back(): void
    {
        $user = $this->user(['configuration']);
        $this->actingAs($user, 'user');

        $response = $this->from(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'settings']))
            ->post(route('admin.configuration.store'), [
                'general' => [
                    'settings' => [
                        'menu_color' => [
                            'brand_color' => '#AABBCC',
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.configuration.index', ['slug' => 'general', 'slug2' => 'settings']));
        $this->assertSame('#AABBCC', core()->getConfigData('general.settings.menu_color.brand_color'));
    }

    public function test_configuration_search_endpoint_returns_json_results(): void
    {
        $user = $this->user(['configuration']);
        $this->actingAs($user, 'user');

        $response = $this->getJson(route('admin.configuration.search', ['query' => 'general']));

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_admin_menu_and_acl_contain_configuration(): void
    {
        $menu = config('menu.admin', []);
        $configurationMenu = collect($menu)->where('key', 'configuration')->first();
        $this->assertNotNull($configurationMenu);
        $this->assertSame('admin.configuration.index', $configurationMenu['route']);

        $acl = config('acl', []);
        $configurationAcl = collect($acl)->where('key', 'configuration')->first();
        $this->assertNotNull($configurationAcl);
        $this->assertContains('admin.configuration.index', (array) $configurationAcl['route']);
        $this->assertContains('admin.configuration.store', (array) $configurationAcl['route']);
        $this->assertContains('admin.configuration.search', (array) $configurationAcl['route']);
        $this->assertContains('admin.configuration.download', (array) $configurationAcl['route']);
    }
}
