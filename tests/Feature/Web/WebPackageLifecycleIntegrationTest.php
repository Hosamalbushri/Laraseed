<?php

namespace Tests\Feature\Web;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebPackageLifecycleIntegrationTest extends TestCase
{
    protected Filesystem $filesystem;

    protected array $createdDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem;
        $this->createdDirectories = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdDirectories as $dir) {
            if ($this->filesystem->isDirectory($dir)) {
                $this->filesystem->deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    protected function trackDirectory(string $path): string
    {
        $fullPath = base_path($path);
        $this->createdDirectories[] = $fullPath;

        return $fullPath;
    }

    /**
     * Test the complete 15-stage lifecycle of optional Web packages from creation to removal.
     */
    public function test_complete_web_package_lifecycle_and_production_reliability(): void
    {
        // 1. Start with zero optional Web packages / baseline state
        config([
            'laraseed.optional_packages.enabled' => [],
            'laraseed.default_web_package'       => null,
            'laraseed.web.default_package'       => null,
        ]);

        // 2. Request GET / and verify fallback view rendering
        $res1 = $this->get('/');
        $res1->assertOk();
        $res1->assertViewIs('web.fallback');
        $res1->assertSee('Platform Active');

        // 3. Generate first Web package
        $pkg1Dir = $this->trackDirectory('packages/AcmeWeb/LifecyclePortalPkg');
        $this->artisan('laraseed:make-package AcmeWeb/LifecyclePortalPkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/LifecyclePortalPkg')->assertExitCode(0);

        // 4. Confirm that generation does NOT automatically activate it
        $this->assertFalse(in_array('lifecycle_portal_pkg', config('laraseed.optional_packages.enabled', []), true));
        $res2 = $this->get('/');
        $res2->assertOk();
        $res2->assertViewIs('web.fallback');

        // 5. Activate the package
        config(['laraseed.optional_packages.enabled' => ['lifecycle_portal_pkg']]);

        // Register package routes in test container
        Route::get('/portal-home', fn () => 'Portal Home')->name('acmeweb_lifecycle_portal_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        // 6. Confirm that activation does NOT automatically select it as default
        $this->assertNull(config('laraseed.default_web_package'));
        $res3 = $this->get('/');
        $res3->assertOk();
        $res3->assertViewIs('web.fallback');

        // 7. Select package as default Web entry point
        config(['laraseed.default_web_package' => 'lifecycle_portal_pkg']);

        // 8. Verify that GET / redirects to its public entry route
        $res4 = $this->get('/');
        $res4->assertRedirect('/portal-home');

        // 9. Generate and activate a second Web package
        $pkg2Dir = $this->trackDirectory('packages/AcmeWeb/LifecycleStorePkg');
        $this->artisan('laraseed:make-package AcmeWeb/LifecycleStorePkg')->assertExitCode(0);
        $this->artisan('laraseed:make-web AcmeWeb/LifecycleStorePkg')->assertExitCode(0);

        Route::get('/store-home', fn () => 'Store Home')->name('acmeweb_lifecycle_store_pkg.web.home');
        Route::getRoutes()->refreshNameLookups();

        config(['laraseed.optional_packages.enabled' => ['lifecycle_portal_pkg', 'lifecycle_store_pkg']]);

        // 10. Switch the default between both packages
        config(['laraseed.default_web_package' => 'lifecycle_store_pkg']);
        $res5 = $this->get('/');
        $res5->assertRedirect('/store-home');

        // 11. Disable the selected package (remove from active list)
        config(['laraseed.optional_packages.enabled' => ['lifecycle_portal_pkg']]); // store is now disabled

        // 12. Confirm safe fallback behavior when default package is disabled
        $res6 = $this->get('/');
        $res6->assertOk();
        $res6->assertViewIs('web.fallback');

        // 13. Clear the default selection
        config(['laraseed.default_web_package' => null]);
        $res7 = $this->get('/');
        $res7->assertOk();
        $res7->assertViewIs('web.fallback');

        // 14. Remove a disposable package safely from disk
        if ($this->filesystem->isDirectory($pkg2Dir)) {
            $this->filesystem->deleteDirectory($pkg2Dir);
        }

        // 15. Verify that the application still boots and serves GET / cleanly
        $res8 = $this->get('/');
        $res8->assertOk();
        $res8->assertViewIs('web.fallback');
    }
}
