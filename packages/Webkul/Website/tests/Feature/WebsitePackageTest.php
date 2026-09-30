<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\Web\Contracts\NavigationRegistryContract;
use Webkul\Web\Contracts\SectionRegistryContract;
use Webkul\Website\Providers\WebsiteServiceProvider;

uses(DatabaseTransactions::class, InteractsWithOptionalPackageComposition::class);

it('verifies website package boots when enabled', function () {
    expect(app()->getProvider(WebsiteServiceProvider::class))->toBeInstanceOf(WebsiteServiceProvider::class);
});

it('verifies website provider registers sections on web home', function () {
    $sectionRegistry = app(SectionRegistryContract::class);
    $sections = $sectionRegistry->getSections('home');

    expect($sections->pluck('key')->all())->toContain('website_hero', 'website_features', 'website_announcements');
});

it('verifies website navigation items are registered in header and footer', function () {
    $navigation = app(NavigationRegistryContract::class);

    $headerItems = $navigation->getItems('header');
    expect($headerItems->pluck('id')->all())->toContain('website_home', 'website_about');

    $footerItems = $navigation->getItems('footer');
    expect($footerItems->pluck('id')->all())->toContain('website_footer_about');
});

it('renders website sections on the public root route', function () {
    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertSee('website-hero', false)
        ->assertSee('Welcome to University CampusHub', false)
        ->assertSee('website-features', false)
        ->assertSee('Academic Excellence', false)
        ->assertSee('website-announcements', false)
        ->assertDontSee('web-home__empty', false);
});

it('renders the website about page with proper localization', function () {
    $response = $this->withSession(['web_locale' => 'en'])->get('/about');

    $response->assertOk()
        ->assertSee('About Our Campus', false)
        ->assertSee('Empowering Future Leaders', false);
});

it('renders arabic rtl on website about page', function () {
    $response = $this->withSession(['web_locale' => 'ar'])->get('/about');

    $response->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('عن الحرم الجامعي', false)
        ->assertSee('تمكين قادة المستقبل', false);
});

it('renders arabic rtl on website root page', function () {
    $response = $this->withSession(['web_locale' => 'ar'])->get('/');

    $response->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('مرحباً بكم في منصة الحرم الجامعي', false)
        ->assertSee('منصة الحرم الجامعي الرسمية', false);
});

it('confirms website contains zero database migrations or models', function () {
    $migrationDir = base_path('packages/Webkul/Website/src/Database/Migrations');
    $modelsDir = base_path('packages/Webkul/Website/src/Models');

    expect(is_dir($migrationDir))->toBeFalse()
        ->and(is_dir($modelsDir))->toBeFalse();
});

it('does not register lost-found routes when lost_and_found is not in composition', function () {
    $composition = app(\Webkul\Core\Packages\OptionalPackageComposition::class);

    if (! in_array('lost_and_found', $composition->enabledPackages(), true)) {
        expect(app('router')->getRoutes()->hasNamedRoute('website.lost_found.index'))->toBeFalse()
            ->and(app('router')->getRoutes()->hasNamedRoute('website.lost_found.show'))->toBeFalse();

        $this->get('/lost-found')->assertNotFound();
    } else {
        expect(app('router')->getRoutes()->hasNamedRoute('website.lost_found.index'))->toBeTrue()
            ->and(app('router')->getRoutes()->hasNamedRoute('website.lost_found.show'))->toBeTrue();
    }
});

it('confirms website has zero dependencies on student and strictly limits lost_and_found to public contracts', function () {
    $websiteSrc = base_path('packages/Webkul/Website/src');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($websiteSrc, FilesystemIterator::SKIP_DOTS),
    );

    $studentViolations = [];
    $modelViolations = [];
    $repositoryViolations = [];
    $tableViolations = [];
    $coreLostFoundViolations = [];

    $forbiddenTables = [
        'lost_found_items',
        'lost_found_reports',
        'lost_found_claims',
        'lost_found_item_images',
        'lost_found_report_images',
        'lost_found_custody_records',
        'lost_found_handovers',
        'lost_found_categories',
    ];

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $pathname = $file->getPathname();
        $content = file_get_contents($pathname);

        // 1. Zero dependencies on Student across entire Website package
        if (str_contains($content, 'Webkul\\Student')) {
            $studentViolations[] = $pathname;
        }

        // 2. Zero imports of LostAndFound Models anywhere
        if (str_contains($content, 'Webkul\\LostAndFound\\Models')) {
            $modelViolations[] = $pathname;
        }

        // 3. Zero imports of LostAndFound Repositories anywhere
        if (str_contains($content, 'Webkul\\LostAndFound\\Repositories')) {
            $repositoryViolations[] = $pathname;
        }

        // 4. Zero database table knowledge anywhere
        foreach ($forbiddenTables as $table) {
            if (str_contains($content, $table)) {
                $tableViolations[] = "{$pathname} references {$table}";
            }
        }

        // 5. Website core (outside src/Integrations and tests/Feature/WebsiteLostAndFound) must not reference LostAndFound
        $isIntegrationFile = str_contains($pathname, '/Integrations/') || str_contains($pathname, 'WebsiteLostAndFound');
        if (! $isIntegrationFile && str_contains($content, 'Webkul\\LostAndFound')) {
            $coreLostFoundViolations[] = $pathname;
        }
    }

    expect($studentViolations)->toBe([])
        ->and($modelViolations)->toBe([])
        ->and($repositoryViolations)->toBe([])
        ->and($tableViolations)->toBe([])
        ->and($coreLostFoundViolations)->toBe([]);
});
