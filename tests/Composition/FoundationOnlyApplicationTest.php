<?php

namespace Tests\Composition;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\Core\Contracts\AuthenticationRedirectResolver;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Services\ContentLocaleService;
use Webkul\DataGrid\Contracts\SavedFilter as SavedFilterContract;
use Webkul\DataGrid\Models\SavedFilter;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as InstallerDatabaseSeeder;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\LostAndFound\Contracts\FoundItem as FoundItemContract;
use Webkul\LostAndFound\Providers\LostAndFoundServiceProvider;
use Webkul\Student\Providers\StudentServiceProvider;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Contracts\User as UserContract;
use Webkul\User\Models\User;
use Webkul\User\Providers\UserServiceProvider;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Web\Providers\WebServiceProvider;

class FoundationOnlyApplicationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_repository_default_is_foundation_only_without_an_override(): void
    {
        $source = file_get_contents(config_path('campushub.php'));
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("env('CAMPUSHUB_OPTIONAL_PACKAGES', '')", $source);
        $this->assertMatchesRegularExpression('/^CAMPUSHUB_OPTIONAL_PACKAGES=$/m', $example);
        $this->assertSame([], config('campushub.optional_packages.enabled'));
        $this->assertSame([], app(OptionalPackageComposition::class)->enabledPackages());
    }

    public function test_foundation_only_boot_has_no_optional_runtime_contributions(): void
    {
        $composition = app(OptionalPackageComposition::class);
        $routes = collect(Route::getRoutes()->getRoutes());
        $optionalRoutes = $routes->filter(fn (RoutingRoute $route): bool => str_starts_with($route->getActionName(), 'Webkul\\Student\\')
            || str_starts_with($route->getActionName(), 'Webkul\\LostAndFound\\'));

        $this->assertSame([], $composition->enabledPackages());
        $this->assertCount(69, $routes);
        $this->assertCount(0, $optionalRoutes);
        $this->assertFalse(app()->providerIsLoaded(StudentServiceProvider::class));
        $this->assertFalse(app()->providerIsLoaded(LostAndFoundServiceProvider::class));
        $this->assertSame([], config('campushub.optional_packages.concord_modules'));
        $this->assertNull(app('concord')->model(FoundItemContract::class));
        $this->assertArrayNotHasKey('student', config('auth.guards'));
        $this->assertArrayNotHasKey('students', config('auth.providers'));
        $this->assertNull(config('filesystems.disks.lost_found_private'));
        $this->assertFalse(app(NavigationRegistryContract::class)->has('event.events', 'header'));

        $viewHints = app('view')->getFinder()->getHints();
        $this->assertArrayNotHasKey('student', $viewHints);
        $this->assertArrayNotHasKey('event', $viewHints);
        $this->assertArrayNotHasKey('lost_found', $viewHints);

        $aclKeys = collect(config('acl', []))->pluck('key');
        $menuKeys = collect(config('menu.admin', []))->pluck('key');
        $this->assertFalse($aclKeys->contains(fn (string $key): bool => str_starts_with($key, 'students') || str_starts_with($key, 'events') || str_starts_with($key, 'lost_found')));
        $this->assertFalse($menuKeys->contains(fn (string $key): bool => str_starts_with($key, 'students') || str_starts_with($key, 'events') || str_starts_with($key, 'lost_found')));

        $migrationPaths = app('migrator')->paths();
        $this->assertFalse(collect($migrationPaths)->contains(fn (string $path): bool => str_contains($path, '/Student/')));
        $this->assertFalse(collect($migrationPaths)->contains(fn (string $path): bool => str_contains($path, '/Event/')));
        $this->assertFalse(collect($migrationPaths)->contains(fn (string $path): bool => str_contains($path, '/LostAndFound/')));
    }

    public function test_all_foundation_providers_and_core_services_are_operational(): void
    {
        foreach ([
            CoreServiceProvider::class,
            UserServiceProvider::class,
            AdminServiceProvider::class,
            DataGridServiceProvider::class,
            InstallerServiceProvider::class,
            WebServiceProvider::class,
            ThemeServiceProvider::class,
        ] as $provider) {
            $this->assertTrue(app()->providerIsLoaded($provider), "Foundation provider [{$provider}] was not loaded.");
        }

        $this->assertInstanceOf(AuthenticationRedirectResolver::class, app(AuthenticationRedirectResolver::class));
        $this->assertInstanceOf(ContentLocaleService::class, app(ContentLocaleService::class));
        $this->assertNotNull(app('concord')->model(UserContract::class));
    }

    public function test_user_auth_and_datagrid_infrastructure_work_without_optional_packages(): void
    {
        $this->assertSame('user', config('auth.defaults.guard'));
        $this->assertArrayHasKey('user', config('auth.guards'));
        $this->assertArrayHasKey('users', config('auth.providers'));
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertSame(SavedFilter::class, app('concord')->model(SavedFilterContract::class));

        $admin = User::findOrFail(1);
        $this->actingAs($admin, 'user');

        $this->assertAuthenticatedAs($admin, 'user');
    }

    public function test_foundation_only_root_web_context_localization_and_base_theme_work(): void
    {
        $this->withSession(['web_locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr" data-theme="base">', false);

        $this->assertSame('en', app(WebContextContract::class)->locale());
        $this->assertSame('ltr', app(WebContextContract::class)->direction());
        $this->assertSame('base', app(ThemeResolverContract::class)->resolveActiveTheme()->id);

        $this->withSession(['web_locale' => 'ar'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl" data-theme="base">', false);

        $this->assertSame('ar', app(WebContextContract::class)->locale());
        $this->assertSame('rtl', app(WebContextContract::class)->direction());
        $this->assertNotSame('admin::app', trans('admin::app.components.layouts.header.mega-search.title'));
        $this->assertNotSame('web::app', trans('web::app.home.seo.title'));
    }

    public function test_web_registries_seo_and_components_work_without_optional_contributions(): void
    {
        $navigation = app(NavigationRegistryContract::class);
        $sections = app(SectionRegistryContract::class);
        $seo = app(SeoMetadataContract::class);

        $this->assertCount(0, $navigation->getItems('header'));
        $this->assertCount(0, $sections->getSections('home'));

        $seo->setTitle('Foundation')->setDescription('Foundation baseline');
        $this->assertSame('Foundation | CampusHub', $seo->getTitle());
        $this->assertSame('Foundation baseline', $seo->getDescription());
        $this->assertStringContainsString('<title>Foundation | CampusHub</title>', $seo->renderHeadHtml());

        $component = Blade::render('<x-web::button>Foundation action</x-web::button>');
        $this->assertStringContainsString('<button', $component);
        $this->assertStringContainsString('Foundation action', $component);
    }

    public function test_theme_registry_resolver_and_base_inheritance_are_operational(): void
    {
        $registry = app(ThemeRegistryContract::class);
        $resolver = app(ThemeResolverContract::class);

        $this->assertTrue($registry->has('base'));
        $this->assertSame('base', $resolver->resolveActiveTheme()->id);
        $this->assertSame(['base'], array_map(
            fn ($theme): string => $theme->id,
            $resolver->resolveActiveInheritanceChain(),
        ));
    }

    public function test_foundation_only_admin_shell_and_extension_hosts_work(): void
    {
        $this->get(route('admin.session.create'))->assertOk();

        $admin = User::findOrFail(1);
        $response = $this->actingAs($admin, 'user')->get(route('admin.dashboard.index'));

        $response->assertOk();
        $this->assertTrue(Route::has('admin.dashboard.index'));
        $this->assertIsArray(config('acl'));
        $this->assertIsArray(config('menu.admin'));

        $megaSearch = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/header/desktop/mega-search.blade.php'));
        $quickCreate = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/header/quick-creation.blade.php'));

        $this->assertStringContainsString('view_render_event', $megaSearch);
        $this->assertStringContainsString('view_render_event', $quickCreate);
        $this->assertStringNotContainsString('student::', $megaSearch.$quickCreate);
        $this->assertStringNotContainsString('event::', $megaSearch.$quickCreate);
        $this->assertStringNotContainsString('lost_found::', $megaSearch.$quickCreate);
    }

    public function test_installer_bootstrap_has_only_foundation_seed_dependencies(): void
    {
        $this->assertTrue(Route::has('installer.index'));
        $this->assertTrue(Route::has('installer.run_migration'));
        $this->assertTrue(view()->exists('installer::installer.index'));
        $this->assertNotSame('installer::app', trans('installer::app.installer.index.title'));

        $seeder = file_get_contents(base_path('packages/Webkul/Installer/src/Database/Seeders/DatabaseSeeder.php'));
        $this->assertStringContainsString('CoreSeeder::class', $seeder);
        $this->assertStringContainsString('UserSeeder::class', $seeder);
        $this->assertStringNotContainsString('Student', $seeder);
        $this->assertStringNotContainsString('Event', $seeder);
        $this->assertStringNotContainsString('LostAndFound', $seeder);
        $this->assertTrue(is_subclass_of(InstallerDatabaseSeeder::class, Seeder::class));
    }

    public function test_route_ownership_is_foundation_only_and_measured(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        $frameworkRoutes = $routes->filter(fn (RoutingRoute $route): bool => str_starts_with(ltrim($route->getActionName(), '\\'), 'Illuminate\\')
            || str_starts_with(ltrim($route->getActionName(), '\\'), 'Laravel\\')
            || $route->getActionName() === 'Closure');

        $this->assertCount(69, $routes);
        $this->assertCount(3, $frameworkRoutes);
        $this->assertSame(66, $routes->count() - $frameworkRoutes->count());

        $root = $routes->first(fn (RoutingRoute $route): bool => $route->uri() === '/' && in_array('GET', $route->methods(), true));
        $this->assertNotNull($root);
        $this->assertSame('web.home', $root->getName());
        $this->assertSame('Webkul\\Web\\Http\\Controllers\\HomeController@index', $root->getActionName());
    }
}
