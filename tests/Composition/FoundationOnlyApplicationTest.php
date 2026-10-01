<?php

namespace Tests\Composition;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as RoutingRoute;
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
use Webkul\User\Contracts\User as UserContract;
use Webkul\User\Models\User;
use Webkul\User\Providers\UserServiceProvider;

class FoundationOnlyApplicationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_repository_default_is_foundation_only_without_an_override(): void
    {
        $source = file_get_contents(config_path('laraseed.php'));
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("env('LARASEED_OPTIONAL_PACKAGES', '')", $source);
        $this->assertMatchesRegularExpression('/^LARASEED_OPTIONAL_PACKAGES=$/m', $example);
        $this->assertSame([], config('laraseed.optional_packages.enabled'));
        $this->assertSame([], app(OptionalPackageComposition::class)->enabledPackages());
    }

    public function test_foundation_only_boot_has_no_optional_runtime_contributions(): void
    {
        $composition = app(OptionalPackageComposition::class);
        $routes = collect(Route::getRoutes()->getRoutes());
        $optionalRoutes = $routes->filter(fn (RoutingRoute $route): bool => str_starts_with($route->getActionName(), 'Webkul\\Student\\')
            || str_starts_with($route->getActionName(), 'Webkul\\LostAndFound\\')
            || str_starts_with($route->getActionName(), 'Webkul\\Website\\')
            || str_starts_with($route->getActionName(), 'Webkul\\Web\\'));

        $this->assertSame([], $composition->enabledPackages());
        $this->assertCount(67, $routes);
        $this->assertCount(0, $optionalRoutes);
        $this->assertSame([], config('laraseed.optional_packages.concord_modules'));
        $this->assertArrayNotHasKey('student', config('auth.guards'));
        $this->assertArrayNotHasKey('students', config('auth.providers'));
        $this->assertNull(config('filesystems.disks.lost_found_private'));

        $viewHints = app('view')->getFinder()->getHints();
        $this->assertArrayNotHasKey('student', $viewHints);
        $this->assertArrayNotHasKey('event', $viewHints);
        $this->assertArrayNotHasKey('lost_found', $viewHints);
        $this->assertArrayNotHasKey('web', $viewHints);
        $this->assertArrayNotHasKey('website', $viewHints);

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

    public function test_view_finder_is_standard_laravel_file_view_finder(): void
    {
        $finder = app('view.finder');
        $this->assertInstanceOf(\Illuminate\View\FileViewFinder::class, $finder);
        $this->assertFalse(str_contains(get_class($finder), 'ThemeViewFinder'));
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

        $this->assertCount(67, $routes);
        $this->assertCount(3, $frameworkRoutes);
        $this->assertSame(64, $routes->count() - $frameworkRoutes->count());

        $root = $routes->first(fn (RoutingRoute $route): bool => $route->uri() === '/' && in_array('GET', $route->methods(), true));
        $this->assertNull($root, 'Public root / route is intentionally absent in Foundation-only state');
    }
}
