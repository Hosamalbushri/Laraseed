<?php

namespace Tests\Feature\Web;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WebEntryPointTest extends TestCase
{
    protected Filesystem $filesystem;

    protected array $createdDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem;
        $this->createdDirectories = [];
        $this->clearBootstrapCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdDirectories as $dir) {
            if ($this->filesystem->isDirectory($dir)) {
                $this->filesystem->deleteDirectory($dir);
            }
        }

        $this->clearBootstrapCache();

        parent::tearDown();
    }

    protected function clearBootstrapCache(): void
    {
        $cacheDir = base_path('bootstrap/cache');
        foreach (['config.php', 'routes-v7.php', 'events.php', 'packages.php', 'services.php'] as $file) {
            $path = $cacheDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    protected function trackDirectory(string $path): string
    {
        $fullPath = base_path($path);
        $this->createdDirectories[] = $fullPath;

        return $fullPath;
    }

    /**
     * Scenario 1 & 2: No optional Web packages exist / Root URL returns fallback page.
     */
    public function test_root_url_returns_safe_fallback_page_when_no_default_package_configured(): void
    {
        config([
            'laraseed.default_web_package' => null,
            'laraseed.web.default_package' => null,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('web.fallback');
        $response->assertSee('Platform Active');
        $response->assertSee('The application core is online.');
        $response->assertDontSee('Fatal error');
        $response->assertDontSee('Exception');
    }

    /**
     * Scenario 3: A Web package exists but is disabled.
     */
    public function test_root_url_returns_fallback_page_when_selected_package_is_disabled(): void
    {
        $pkgDir = $this->trackDirectory('packages/AcmeWeb/DisabledBlogPkg');
        $this->artisan('laraseed:make-package AcmeWeb/DisabledBlogPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/DisabledBlogPkg')->assertExitCode(0);

        // Package is NOT in LARASEED_OPTIONAL_PACKAGES
        config([
            'laraseed.optional_packages.enabled' => [],
            'laraseed.default_web_package'       => 'disabled_blog_pkg',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Scenario 4: A Web package is enabled but is not selected as default.
     */
    public function test_root_url_returns_fallback_page_when_web_package_is_enabled_but_not_selected(): void
    {
        $pkgDir = $this->trackDirectory('packages/AcmeWeb/ActiveBlogPkg');
        $this->artisan('laraseed:make-package AcmeWeb/ActiveBlogPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/ActiveBlogPkg')->assertExitCode(0);

        config([
            'laraseed.optional_packages.enabled' => ['active_blog_pkg'],
            'laraseed.default_web_package'       => null,
            'laraseed.web.default_package'       => null,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Scenario 5: One valid Web package is selected as default.
     */
    public function test_root_url_redirects_to_selected_default_package_entry_route(): void
    {
        $pkgDir = $this->trackDirectory('packages/AcmeWeb/MainPortalPkg');
        $this->artisan('laraseed:make-package AcmeWeb/MainPortalPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/MainPortalPkg')->assertExitCode(0);

        Route::get('/acmeweb-main-portal-pkg', fn () => 'Main Portal Home')
            ->name('acmeweb_main_portal_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        config([
            'laraseed.optional_packages.enabled' => ['main_portal_pkg'],
            'laraseed.optional_packages.catalog' => [
                'main_portal_pkg' => [
                    'id'           => 'main_portal_pkg',
                    'capabilities' => [
                        'web' => [
                            'provider'    => 'AcmeWeb\\MainPortalPkg\\Web\\Providers\\WebServiceProvider',
                            'enabled'     => true,
                            'entry_route' => 'acmeweb_main_portal_pkg.web.home',
                        ],
                    ],
                ],
            ],
            'laraseed.default_web_package' => 'main_portal_pkg',
        ]);

        $response = $this->get('/');

        $response->assertRedirect('/acmeweb-main-portal-pkg');
    }

    /**
     * Scenario 6 & 7: Multiple Web packages enabled and changing default between them.
     */
    public function test_switching_default_between_multiple_enabled_web_packages(): void
    {
        $pkg1 = $this->trackDirectory('packages/AcmeWeb/ShopPkg');
        $pkg2 = $this->trackDirectory('packages/AcmeWeb/BlogPkg');
        $this->artisan('laraseed:make-package AcmeWeb/ShopPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/ShopPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-package AcmeWeb/BlogPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/BlogPkg')->assertExitCode(0);

        Route::get('/shop-home', fn () => 'Shop Home')->name('acmeweb_shop_pkg.web.home');
        Route::get('/blog-home', fn () => 'Blog Home')->name('acmeweb_blog_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        config([
            'laraseed.optional_packages.enabled' => ['shop_pkg', 'blog_pkg'],
            'laraseed.optional_packages.catalog' => [
                'shop_pkg' => [
                    'id'           => 'shop_pkg',
                    'capabilities' => [
                        'web' => ['entry_route' => 'acmeweb_shop_pkg.web.home', 'enabled' => true],
                    ],
                ],
                'blog_pkg' => [
                    'id'           => 'blog_pkg',
                    'capabilities' => [
                        'web' => ['entry_route' => 'acmeweb_blog_pkg.web.home', 'enabled' => true],
                    ],
                ],
            ],
        ]);

        // 1. Set default to shop_pkg
        config(['laraseed.default_web_package' => 'shop_pkg']);
        $this->get('/')->assertRedirect('/shop-home');

        // 2. Switch default to blog_pkg
        config(['laraseed.default_web_package' => 'blog_pkg']);
        $this->get('/')->assertRedirect('/blog-home');
    }

    /**
     * Scenario 8 & 9: Selected package becomes disabled or removed.
     */
    public function test_root_url_falls_back_when_selected_package_is_removed_or_not_found(): void
    {
        config([
            'laraseed.optional_packages.enabled' => ['some_other_pkg'],
            'laraseed.default_web_package'       => 'non_existent_pkg',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Scenario 10: Selected package has no valid entry route.
     */
    public function test_root_url_falls_back_when_package_has_no_valid_registered_entry_route(): void
    {
        config([
            'laraseed.optional_packages.enabled' => ['broken_route_pkg'],
            'laraseed.optional_packages.catalog' => [
                'broken_route_pkg' => [
                    'id'           => 'broken_route_pkg',
                    'capabilities' => [
                        'web' => ['entry_route' => 'unregistered.ghost.route', 'enabled' => true],
                    ],
                ],
            ],
            'laraseed.default_web_package' => 'broken_route_pkg',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Scenario 11: Generated Web packages declare compatible entry route contract.
     */
    public function test_generated_web_package_satisfies_entry_route_contract(): void
    {
        $pkgDir = $this->trackDirectory('packages/AcmeWeb/ContractVerifyPkg');
        $this->artisan('laraseed:make-package AcmeWeb/ContractVerifyPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/ContractVerifyPkg')->assertExitCode(0);

        $composerData = json_decode((string) file_get_contents($pkgDir . '/composer.json'), true);
        $webCap = $composerData['extra']['laraseed']['capabilities']['web'] ?? null;

        $this->assertNotNull($webCap);
        $this->assertTrue($webCap['enabled']);

        // Check generated Web configuration and routes
        $configFile = (string) file_get_contents($pkgDir . '/src/Web/Config/web.php');
        $routesFile = (string) file_get_contents($pkgDir . '/src/Web/Routes/web.php');

        $this->assertStringContainsString("'route' => 'acmeweb_contract_verify_pkg.web.home'", $configFile);
        $this->assertStringContainsString("Route::name('acmeweb_contract_verify_pkg.web.')->group", $routesFile);
        $this->assertStringContainsString("->name('home')", $routesFile);
    }

    /**
     * Scenario 12: Route and Configuration Cache Compatibility.
     */
    public function test_configuration_and_route_cache_compatibility(): void
    {
        $procClear = new Process(['php', 'artisan', 'optimize:clear'], base_path());
        $procClear->run();
        $this->assertSame(0, $procClear->getExitCode());

        $procConfigCache = new Process(['php', 'artisan', 'config:cache'], base_path());
        $procConfigCache->run();
        $this->assertSame(0, $procConfigCache->getExitCode(), $procConfigCache->getErrorOutput());

        $procRouteCache = new Process(['php', 'artisan', 'route:cache'], base_path());
        $procRouteCache->run();
        $this->assertSame(0, $procRouteCache->getExitCode(), $procRouteCache->getErrorOutput());

        // Cleanup cache
        $procClear = new Process(['php', 'artisan', 'optimize:clear'], base_path());
        $procClear->run();
    }

    /**
     * Scenario 13: Exactly one root route exists.
     */
    public function test_no_duplicate_root_routes_exist(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        $rootRoutes = $routes->filter(fn ($r) => $r->uri() === '/' && in_array('GET', $r->methods(), true));

        $this->assertCount(1, $rootRoutes);
        $this->assertSame('laraseed.web.entry', $rootRoutes->first()->getName());
    }

    /**
     * Scenario 14: Redirect loop prevention when entry route points back to entry.
     */
    public function test_redirect_loop_prevention_when_target_route_points_to_root(): void
    {
        Route::get('/some-root-alias', fn () => 'Root Alias')->name('root.alias.home');

        config([
            'laraseed.optional_packages.enabled' => ['loop_pkg'],
            'laraseed.optional_packages.catalog' => [
                'loop_pkg' => [
                    'id'           => 'loop_pkg',
                    'capabilities' => [
                        'web' => ['entry_route' => 'laraseed.web.entry', 'enabled' => true],
                    ],
                ],
            ],
            'laraseed.default_web_package' => 'loop_pkg',
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Scenario 15: Existing Admin functionality remains unaffected.
     */
    public function test_admin_routes_and_login_remain_independently_functional(): void
    {
        $response = $this->get('/admin/login');
        $response->assertOk();

        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Hardening Scenario 16: External domain route target is rejected (Open Redirect Protection).
     */
    public function test_external_domain_route_target_is_rejected_and_serves_fallback(): void
    {
        Route::domain('external.example.com')->group(function () {
            Route::get('/external-portal', fn () => 'External Portal')->name('external_pkg.web.home');
        });
        Route::getRoutes()->refreshNameLookups();

        config([
            'laraseed.optional_packages.enabled' => ['external_pkg'],
            'laraseed.default_web_package'       => 'external_pkg',
            'laraseed.web.entry_routes.external_pkg' => 'external_pkg.web.home',
        ]);

        $response = $this->get('/');

        // Must reject redirecting to external domain and safely render fallback
        $response->assertOk();
        $response->assertViewIs('web.fallback');
    }

    /**
     * Hardening Scenario 17: Conflicting configuration keys honor deterministic precedence.
     */
    public function test_conflicting_configuration_values_honor_deterministic_precedence(): void
    {
        Route::get('/primary-target', fn () => 'Primary Target')->name('primary_pkg.web.home');
        Route::get('/legacy-target', fn () => 'Legacy Target')->name('legacy_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        config([
            'laraseed.optional_packages.enabled' => ['primary_pkg', 'legacy_pkg'],
            'laraseed.default_web_package'       => 'primary_pkg',
            'laraseed.web.default_package'       => 'legacy_pkg',
        ]);

        // Primary laraseed.default_web_package takes absolute precedence
        $response = $this->get('/');
        $response->assertRedirect('/primary-target');

        // When primary is null, fallback to legacy
        config(['laraseed.default_web_package' => null]);
        $response2 = $this->get('/');
        $response2->assertRedirect('/legacy-target');
    }

    /**
     * Hardening Scenario 18: Hyphenated and snake_case package identifiers resolve interchangeably.
     */
    public function test_hyphenated_and_snake_case_package_identifiers_resolve_interchangeably(): void
    {
        Route::get('/kebab-target', fn () => 'Kebab Target')->name('kebab_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        // Enabled contains kebab-pkg, config specifies kebab-pkg
        config([
            'laraseed.optional_packages.enabled' => ['kebab-pkg'],
            'laraseed.default_web_package'       => 'kebab-pkg',
        ]);

        $response = $this->get('/');
        $response->assertRedirect('/kebab-target');

        // Enabled contains kebab_pkg, config specifies kebab-pkg
        config([
            'laraseed.optional_packages.enabled' => ['kebab_pkg'],
            'laraseed.default_web_package'       => 'kebab-pkg',
        ]);

        $response2 = $this->get('/');
        $response2->assertRedirect('/kebab-target');
    }

    /**
     * Hardening Scenario 19: Malformed or malicious package identifier strings are rejected.
     */
    public function test_malformed_package_identifiers_are_rejected_and_serve_fallback(): void
    {
        config([
            'laraseed.optional_packages.enabled' => ['valid_pkg'],
            'laraseed.default_web_package'       => '../../malicious/path',
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertViewIs('web.fallback');

        config(['laraseed.default_web_package' => "pkg\0nullbyte"]);
        $response2 = $this->get('/');
        $response2->assertOk();
        $response2->assertViewIs('web.fallback');
    }

    /**
     * Hardening Scenario 20: Application-level explicit override takes precedence over package defaults.
     */
    public function test_application_level_explicit_entry_route_override_precedence(): void
    {
        Route::get('/default-home', fn () => 'Default Home')->name('override_pkg.web.home');
        Route::get('/custom-landing', fn () => 'Custom Landing')->name('custom.landing.route');
        Route::getRoutes()->refreshNameLookups();

        config([
            'laraseed.optional_packages.enabled' => ['override_pkg'],
            'laraseed.default_web_package'       => 'override_pkg',
            'laraseed.web.entry_routes.override_pkg' => 'custom.landing.route',
        ]);

        $response = $this->get('/');
        $response->assertRedirect('/custom-landing');
    }

    /**
     * Hardening Scenario 21: Fallback page security, lack of inline scripts, and zero information disclosure.
     */
    public function test_fallback_page_security_and_zero_information_disclosure(): void
    {
        config([
            'laraseed.default_web_package' => null,
            'laraseed.web.default_package' => null,
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // 1. Zero executable inline JavaScript
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringNotContainsString('onload=', $content);
        $this->assertStringNotContainsString('onerror=', $content);
        $this->assertStringNotContainsString('javascript:', $content);

        // 2. Zero information disclosure
        $this->assertStringNotContainsString(base_path(), $content);
        $this->assertStringNotContainsString('DB_', $content);
        $this->assertStringNotContainsString('APP_KEY', $content);
        $this->assertStringNotContainsString('Stack trace', $content);

        // 3. Semantic markup
        $this->assertStringContainsString('<main class="container"', $content);
        $this->assertStringContainsString('role="status"', $content);
    }
}
