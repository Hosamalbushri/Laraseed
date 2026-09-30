<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicCategoryData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchResult;
use Webkul\Web\Contracts\NavigationRegistryContract;

uses(DatabaseTransactions::class, InteractsWithOptionalPackageComposition::class);

final class ShellChromeFakeLostAndFoundReader implements PublicLostAndFoundReadContract
{
    /**
     * @param list<PublicFoundItemData> $items
     * @param list<PublicCategoryData> $categories
     */
    public function __construct(
        private array $items = [],
        private array $categories = [],
    ) {}

    public function getRecentPublicFoundItems(int $limit = 6): array
    {
        return array_slice($this->items, 0, $limit);
    }

    public function searchPublicFoundItems(PublicFoundItemSearchCriteria $criteria): PublicFoundItemSearchResult
    {
        return new PublicFoundItemSearchResult(
            items: $this->items,
            total: count($this->items),
            perPage: $criteria->perPage,
            currentPage: $criteria->page,
            lastPage: 1,
        );
    }

    public function findPublicFoundItemByReference(string $reference): ?PublicFoundItemData
    {
        foreach ($this->items as $item) {
            if (strcasecmp($item->reference, $reference) === 0) {
                return $item;
            }
        }

        return null;
    }

    public function getPublicCategories(): array
    {
        return $this->categories;
    }
}

it('renders Website header brand with and without logo using SiteDefinitionContract', function () {
    // 1. Default without logo: renders site name, no broken <img> tag
    config(['website.branding.logo_url' => null]);

    $withoutLogo = $this->withSession(['web_locale' => 'en'])->get('/');
    $withoutLogo->assertOk()
        ->assertSee('data-website-header', false)
        ->assertSee('data-website-header-brand', false)
        ->assertSee('University CampusHub')
        ->assertDontSee('data-website-header-logo', false);

    // 2. With valid logo: renders constrained logo + localized alt + site name
    config([
        'website.branding.logo_url' => '/images/brand/crest.svg',
        'website.branding.logo_alt' => [
            'en' => 'Official University Crest',
            'ar' => 'الشعار الرسمي للجامعة',
        ],
    ]);

    $withLogoEn = $this->withSession(['web_locale' => 'en'])->get('/');
    $withLogoEn->assertOk()
        ->assertSee('data-website-header-logo', false)
        ->assertSee('src="/images/brand/crest.svg"', false)
        ->assertSee('alt="Official University Crest"', false)
        ->assertSee('University CampusHub');

    $withLogoAr = $this->withSession(['web_locale' => 'ar'])->get('/');
    $withLogoAr->assertOk()
        ->assertSee('data-website-header-logo', false)
        ->assertSee('src="/images/brand/crest.svg"', false)
        ->assertSee('alt="الشعار الرسمي للجامعة"', false)
        ->assertSee('منصة الحرم الجامعي');
});

it('renders primary and footer navigation strictly from NavigationRegistryContract', function () {
    $navigation = app(NavigationRegistryContract::class);

    $navigation->register([
        'id'       => 'custom_header_item',
        'title'    => 'Academic Calendar',
        'url'      => '/calendar',
        'location' => 'header',
        'order'    => 99,
    ]);

    $navigation->register([
        'id'       => 'custom_footer_item',
        'title'    => 'Campus Policies',
        'url'      => '/policies',
        'location' => 'footer',
        'order'    => 99,
    ]);

    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertSee('>Academic Calendar</a>', false)
        ->assertSee('href="/calendar"', false)
        ->assertSee('>Campus Policies</a>', false)
        ->assertSee('href="/policies"', false);
});

it('marks the active navigation link with aria-current="page" and a visual state across /, /about, /lost-found, and /lost-found/{reference}', function () {
    $this->app->register(\Webkul\Website\Integrations\LostAndFound\WebsiteLostAndFoundServiceProvider::class);
    $this->app->instance(PublicLostAndFoundReadContract::class, new ShellChromeFakeLostAndFoundReader([
        new PublicFoundItemData(
            reference: 'LF-CHROME-01',
            title: 'Blue Notebook',
            category: 'stationery',
            foundLocation: 'Main Hall',
            foundAt: Carbon::parse('2026-09-29 09:00:00'),
        ),
    ]));

    // 1. Root / -> Home is active, About and Lost & Found are not active
    $homeHtml = $this->withSession(['web_locale' => 'en'])->get('/')->assertOk()->getContent();
    expect($homeHtml)->toMatch('/<a[^>]*href="\/"[^>]*aria-current="page"[^>]*>Home<\/a>/')
        ->and($homeHtml)->not->toMatch('/<a[^>]*href="\/about"[^>]*aria-current="page"/')
        ->and($homeHtml)->not->toMatch('/<a[^>]*href="\/lost-found"[^>]*aria-current="page"/');

    // 2. /about -> About Us is active, Home and Lost & Found are not active
    $aboutHtml = $this->withSession(['web_locale' => 'en'])->get('/about')->assertOk()->getContent();
    expect($aboutHtml)->toMatch('/<a[^>]*href="\/about"[^>]*aria-current="page"[^>]*>About Us<\/a>/')
        ->and($aboutHtml)->not->toMatch('/<a[^>]*href="\/"[^>]*aria-current="page"/')
        ->and($aboutHtml)->not->toMatch('/<a[^>]*href="\/lost-found"[^>]*aria-current="page"/');

    // 3. /lost-found -> Lost & Found is active, Home and About are not active
    $lfIndexHtml = $this->withSession(['web_locale' => 'en'])->get('/lost-found')->assertOk()->getContent();
    expect($lfIndexHtml)->toMatch('/<a[^>]*href="\/lost-found"[^>]*aria-current="page"[^>]*>Lost &amp; Found<\/a>/')
        ->and($lfIndexHtml)->not->toMatch('/<a[^>]*href="\/"[^>]*aria-current="page"/')
        ->and($lfIndexHtml)->not->toMatch('/<a[^>]*href="\/about"[^>]*aria-current="page"/');

    // 4. /lost-found/LF-CHROME-01 -> Lost & Found remains active on child detail route
    $lfShowHtml = $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-CHROME-01')->assertOk()->getContent();
    expect($lfShowHtml)->toMatch('/<a[^>]*href="\/lost-found"[^>]*aria-current="page"[^>]*>Lost &amp; Found<\/a>/')
        ->and($lfShowHtml)->not->toMatch('/<a[^>]*href="\/"[^>]*aria-current="page"/')
        ->and($lfShowHtml)->not->toMatch('/<a[^>]*href="\/about"[^>]*aria-current="page"/');
});

it('renders accessible mobile navigation toggle and drawer with navigation and locale switcher', function () {
    $response = $this->withSession(['web_locale' => 'en'])->get('/about');

    $response->assertOk()
        ->assertSee('data-web-drawer-trigger="website-mobile-drawer"', false)
        ->assertSee('aria-controls="website-mobile-drawer-dialog"', false)
        ->assertSee('id="website-mobile-drawer"', false)
        ->assertSee('data-website-mobile-navigation', false)
        ->assertSee('data-website-locale-mobile', false);

    $html = $response->getContent();
    expect($html)->toMatch('/<button[^>]*type="button"[^>]*data-web-drawer-trigger="website-mobile-drawer"[^>]*aria-controls="website-mobile-drawer-dialog"/')
        ->and($html)->toMatch('/<div[^>]*id="website-mobile-drawer-dialog"[^>]*data-web-drawer-dialog/');
});

it('renders locale switcher in header and footer and switches EN -> AR -> EN while preserving current page', function () {
    // 1. On /about in EN, English is active and Arabic switch link is rendered
    $aboutEn = $this->withSession(['web_locale' => 'en'])->get('/about');
    $aboutEn->assertOk()
        ->assertSee('lang="en" dir="ltr"', false)
        ->assertSee('href="/web/locale/ar"', false)
        ->assertSee('data-website-locale-desktop', false);

    // 2. Follow locale switch from /about to AR -> redirects back to /about and renders RTL Arabic
    $switchAr = $this->from('/about')->get('/web/locale/ar');
    $switchAr->assertRedirect('/about');

    $aboutAr = $this->withSession(['web_locale' => 'ar'])->get('/about');
    $aboutAr->assertOk()
        ->assertSee('lang="ar" dir="rtl"', false)
        ->assertSee('href="/web/locale/en"', false)
        ->assertSee('منصة الحرم الجامعي')
        ->assertSee('>الرئيسية</a>', false)
        ->assertSee('>من نحن</a>', false);

    // 3. Switch back to EN -> redirects back to /about and renders LTR English without leakage
    $switchEn = $this->from('/about')->get('/web/locale/en');
    $switchEn->assertRedirect('/about');

    $aboutEnAgain = $this->withSession(['web_locale' => 'en'])->get('/about');
    $aboutEnAgain->assertOk()
        ->assertSee('lang="en" dir="ltr"', false)
        ->assertSee('University CampusHub')
        ->assertSee('>Home</a>', false)
        ->assertSee('>About Us</a>', false)
        ->assertDontSee('>الرئيسية</a>', false);
});

it('renders complete Website footer with identity, navigation, contact details, and SiteDefinition copyright', function () {
    config([
        'app.name' => 'FoundationInternalName',
        'website.identity.name' => [
            'en' => 'Metropolitan University',
            'ar' => 'الجامعة المتروبوليتية',
        ],
        'website.identity.tagline' => [
            'en' => 'Excellence in Research',
            'ar' => 'التميز في البحث العلمي',
        ],
    ]);

    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertSee('data-website-footer', false)
        ->assertSee('data-website-footer-identity', false)
        ->assertSee('Metropolitan University')
        ->assertSee('Excellence in Research')
        ->assertSee('data-website-footer-navigation', false)
        ->assertSee('data-website-footer-contact', false)
        ->assertSee('mailto:info@campushub.edu', false)
        ->assertSee('tel:+15550102000', false)
        ->assertSee('dir="ltr">+1 (555) 010-2000</a>', false)
        ->assertSee('100 University Avenue, Central Campus')
        ->assertSee('Sunday – Thursday, 8:00 AM – 4:00 PM')
        ->assertSee('&copy; '.date('Y').' Metropolitan University.', false)
        ->assertDontSee('FoundationInternalName');
});

it('omits empty contact section and empty contact fields cleanly when contact values are null', function () {
    // 1. Partial contact: only email configured, address/phone/office_hours null
    config([
        'website.contact.email'        => 'helpdesk@campushub.edu',
        'website.contact.phone'        => null,
        'website.contact.address'      => ['en' => null, 'ar' => null],
        'website.contact.office_hours' => ['en' => null, 'ar' => null],
    ]);

    $partialResp = $this->withSession(['web_locale' => 'en'])->get('/');
    $partialResp->assertOk()
        ->assertSee('data-website-footer-contact', false)
        ->assertSee('mailto:helpdesk@campushub.edu', false)
        ->assertDontSee('tel:', false)
        ->assertDontSee('100 University Avenue', false)
        ->assertDontSee('Sunday – Thursday', false);

    // 2. All contact fields null: the complete contact region is omitted
    config([
        'website.contact.email'        => null,
        'website.contact.phone'        => null,
        'website.contact.address'      => ['en' => null, 'ar' => null],
        'website.contact.office_hours' => ['en' => null, 'ar' => null],
    ]);

    $emptyResp = $this->withSession(['web_locale' => 'en'])->get('/');
    $emptyResp->assertOk()
        ->assertSee('data-website-footer-identity', false)
        ->assertDontSee('data-website-footer-contact', false)
        ->assertDontSee('mailto:', false)
        ->assertDontSee('tel:', false);
});

it('executes zero database queries when rendering Website header and footer partials', function () {
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    $siteEn = app(\Webkul\Website\Contracts\SiteDefinitionContract::class)->forLocale('en');
    $siteAr = app(\Webkul\Website\Contracts\SiteDefinitionContract::class)->forLocale('ar');

    $headerEn = view('website::partials.header', ['siteDefinition' => $siteEn])->render();
    $footerEn = view('website::partials.footer', ['siteDefinition' => $siteEn])->render();
    $headerAr = view('website::partials.header', ['siteDefinition' => $siteAr])->render();
    $footerAr = view('website::partials.footer', ['siteDefinition' => $siteAr])->render();

    expect($headerEn)->toContain('data-website-header')
        ->and($footerEn)->toContain('data-website-footer')
        ->and($headerAr)->toContain('data-website-header')
        ->and($footerAr)->toContain('data-website-footer')
        ->and($queries)->toBe([]);
});

it('supplies an optional favicon through the generic Web layout hook without a Website stylesheet', function () {
    config(['website.branding.favicon_url' => '/vendor/website/branding/favicon.svg']);

    $withFavicon = $this->withSession(['web_locale' => 'en'])->get('/');
    $withFavicon->assertOk()
        ->assertSee('<link rel="icon" href="/vendor/website/branding/favicon.svg">', false)
        ->assertDontSee('vendor/website/css/website.css', false);

    config(['website.branding.favicon_url' => null]);

    $withoutFavicon = $this->withSession(['web_locale' => 'en'])->get('/');
    $withoutFavicon->assertOk()
        ->assertDontSee('<link rel="icon"', false)
        ->assertDontSee('vendor/website/css/website.css', false);
});
