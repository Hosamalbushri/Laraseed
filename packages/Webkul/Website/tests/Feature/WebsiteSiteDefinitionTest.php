<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\Web\Context\WebContext;
use Webkul\Web\Contracts\SeoMetadataContract;
use Webkul\Web\Contracts\WebContextContract;
use Webkul\Website\Contracts\SiteDefinitionContract;
use Webkul\Website\SiteDefinition\SiteDefinition;

uses(DatabaseTransactions::class, InteractsWithOptionalPackageComposition::class);

it('binds SiteDefinitionContract and resolves an immutable SiteDefinition value object', function () {
    $resolver = app(SiteDefinitionContract::class);
    $definition = $resolver->forLocale('en');

    $reflection = new ReflectionClass(SiteDefinition::class);

    expect($definition)->toBeInstanceOf(SiteDefinition::class)
        ->and($reflection->isReadOnly())->toBeTrue()
        ->and($definition->locale)->toBe('en')
        ->and($definition->toArray())->toHaveKeys(['locale', 'identity', 'contact', 'branding', 'seo']);
});

it('resolves English site definition identity, contact, branding, and SEO defaults', function () {
    $definition = app(SiteDefinitionContract::class)->forLocale('en');

    expect($definition->name)->toBe('University CampusHub')
        ->and($definition->shortName)->toBe('CampusHub')
        ->and($definition->tagline)->toBe('CampusHub Official Deployment')
        ->and($definition->description)->toBe('Your unified digital portal for campus life, student services, and academic resources.')
        ->and($definition->aboutHeading)->toBe('Empowering Future Leaders')
        ->and($definition->aboutBody)->toContain('academic rigor, pioneering research')
        ->and($definition->email)->toBe('info@campushub.edu')
        ->and($definition->phone)->toBe('+1 (555) 010-2000')
        ->and($definition->address)->toBe('100 University Avenue, Central Campus')
        ->and($definition->officeHours)->toBe('Sunday – Thursday, 8:00 AM – 4:00 PM')
        ->and($definition->hasContact())->toBeTrue()
        ->and($definition->logoUrl)->toBeNull()
        ->and($definition->logoAlt)->toBe('University CampusHub')
        ->and($definition->seoSiteName)->toBe('CampusHub')
        ->and($definition->seoDefaultTitle)->toBe('University CampusHub')
        ->and($definition->seoDefaultDescription)->toContain('unified digital portal');
});

it('resolves Arabic site definition identity, contact, branding, and SEO defaults', function () {
    $definition = app(SiteDefinitionContract::class)->forLocale('ar');

    expect($definition->locale)->toBe('ar')
        ->and($definition->name)->toBe('منصة الحرم الجامعي')
        ->and($definition->shortName)->toBe('الحرم الجامعي')
        ->and($definition->tagline)->toBe('منصة الحرم الجامعي الرسمية')
        ->and($definition->description)->toContain('بوابتكم الرقمية الموحدة')
        ->and($definition->aboutHeading)->toBe('تمكين قادة المستقبل')
        ->and($definition->address)->toBe('100 شارع الجامعة، الحرم الجامعي المركزي')
        ->and($definition->officeHours)->toBe('الأحد – الخميس، 8:00 صباحاً – 4:00 مساءً')
        ->and($definition->logoAlt)->toBe('منصة الحرم الجامعي')
        ->and($definition->seoSiteName)->toBe('منصة الحرم الجامعي')
        ->and($definition->seoDefaultTitle)->toBe('منصة الحرم الجامعي');
});

it('maintains strict sequential locale safety across EN -> AR -> EN without cross-request leakage', function () {
    $resolver = app(SiteDefinitionContract::class);

    // 1. Resolve EN via WebContextContract
    app()->instance(WebContextContract::class, new WebContext(locale: 'en', direction: 'ltr', activeTheme: 'base'));
    $enFirst = $resolver->current();

    // 2. Resolve AR via WebContextContract
    app()->instance(WebContextContract::class, new WebContext(locale: 'ar', direction: 'rtl', activeTheme: 'base'));
    $arSecond = $resolver->current();

    // 3. Resolve EN again via WebContextContract
    app()->instance(WebContextContract::class, new WebContext(locale: 'en', direction: 'ltr', activeTheme: 'base'));
    $enThird = $resolver->current();

    expect($enFirst->locale)->toBe('en')
        ->and($enFirst->name)->toBe('University CampusHub')
        ->and($arSecond->locale)->toBe('ar')
        ->and($arSecond->name)->toBe('منصة الحرم الجامعي')
        ->and($enThird->locale)->toBe('en')
        ->and($enThird->name)->toBe('University CampusHub');

    // Also verify sequential HTTP requests EN -> AR -> EN
    $respEn1 = $this->withSession(['web_locale' => 'en'])->get('/');
    $respEn1->assertOk()
        ->assertSee('lang="en" dir="ltr"', false)
        ->assertSee('Welcome to University CampusHub')
        ->assertDontSee('مرحباً بكم في منصة الحرم الجامعي');

    $respAr = $this->withSession(['web_locale' => 'ar'])->get('/');
    $respAr->assertOk()
        ->assertSee('lang="ar" dir="rtl"', false)
        ->assertSee('مرحباً بكم في منصة الحرم الجامعي')
        ->assertDontSee('Welcome to University CampusHub');

    $respEn2 = $this->withSession(['web_locale' => 'en'])->get('/');
    $respEn2->assertOk()
        ->assertSee('lang="en" dir="ltr"', false)
        ->assertSee('Welcome to University CampusHub')
        ->assertDontSee('مرحباً بكم في منصة الحرم الجامعي');
});

it('falls back deterministically to the configured fallback locale when a localized value is missing', function () {
    config([
        'website.identity.name' => [
            'en' => 'Fallback University',
        ],
        'website.identity.tagline' => [
            'en' => 'Fallback Tagline',
            'ar' => '   ',
        ],
    ]);

    $arDefinition = app(SiteDefinitionContract::class)->forLocale('ar');
    $frDefinition = app(SiteDefinitionContract::class)->forLocale('fr');

    expect($arDefinition->name)->toBe('Fallback University')
        ->and($arDefinition->tagline)->toBe('Fallback Tagline')
        ->and($frDefinition->name)->toBe('Fallback University')
        ->and($frDefinition->tagline)->toBe('Fallback Tagline');
});

it('decouples Website organization identity from Foundation product identity', function () {
    config([
        'app.name' => 'CampusHub',
        'website.identity.name' => [
            'en' => 'Example University',
            'ar' => 'جامعة المثال',
        ],
        'website.seo.site_name' => [
            'en' => 'Example University',
            'ar' => 'جامعة المثال',
        ],
    ]);

    $response = $this->withSession(['web_locale' => 'en'])->get('/about');

    $response->assertOk()
        ->assertSee('Example University')
        ->assertSee('<title>About Our Campus | Example University</title>', false);

    expect(config('app.name'))->toBe('CampusHub');
});

it('supplies SEO defaults to SeoMetadataContract while preserving page-specific overrides', function () {
    $seo = app(SeoMetadataContract::class);

    // Clear page-specific overrides to verify Site Definition defaults
    $seo->setTitle(null)->setDescription(null);

    app()->instance(WebContextContract::class, new WebContext(locale: 'en', direction: 'ltr', activeTheme: 'base'));
    expect($seo->getTitle())->toBe('University CampusHub | CampusHub')
        ->and($seo->getDescription())->toBe('Your unified digital portal for campus life, student services, and academic resources.');

    // Switch to Arabic and verify SEO defaults update per request locale
    app()->instance(WebContextContract::class, new WebContext(locale: 'ar', direction: 'rtl', activeTheme: 'base'));
    expect($seo->getTitle())->toBe('منصة الحرم الجامعي')
        ->and($seo->getDescription())->toContain('بوابتكم الرقمية الموحدة');

    // Page-specific override takes precedence while keeping localized site suffix
    app()->instance(WebContextContract::class, new WebContext(locale: 'en', direction: 'ltr', activeTheme: 'base'));
    $seo->setTitle('Custom Page')->setDescription('Custom page description.');

    expect($seo->getTitle())->toBe('Custom Page | CampusHub')
        ->and($seo->getDescription())->toBe('Custom page description.');
});

it('validates and sanitizes branding URLs and contact fields against unsafe schemes and local paths', function () {
    $resolver = app(SiteDefinitionContract::class);

    // Unsafe URLs and local filesystem paths must be rejected to null
    foreach ([
        'javascript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
        'file:///etc/passwd',
        '//evil.example.com/logo.svg',
        '/home/hosam/Documents/CampusHub-main/logo.svg',
        '/var/www/html/logo.svg',
        '/tmp/logo.svg',
        '/etc/passwd',
        '/images/../secret/logo.svg',
        'C:\\Users\\admin\\logo.png',
    ] as $unsafeUrl) {
        config([
            'website.branding.logo_url' => $unsafeUrl,
            'website.branding.favicon_url' => $unsafeUrl,
            'website.seo.default_image_url' => $unsafeUrl,
        ]);

        $def = $resolver->forLocale('en');

        expect($def->logoUrl)->toBeNull("Failed to reject unsafe logo URL: {$unsafeUrl}")
            ->and($def->faviconUrl)->toBeNull("Failed to reject unsafe favicon URL: {$unsafeUrl}")
            ->and($def->seoDefaultImageUrl)->toBeNull("Failed to reject unsafe SEO image URL: {$unsafeUrl}");
    }

    // Valid relative public path and HTTPS URL must be accepted
    config([
        'website.branding.logo_url' => '/images/brand/university-logo.svg',
        'website.seo.default_image_url' => 'https://example.edu/assets/og-banner.jpg',
        'website.contact.email' => 'not-an-email<script>',
        'website.contact.phone' => 'javascript:alert(1)',
    ]);

    $validDef = $resolver->forLocale('en');
    expect($validDef->logoUrl)->toBe('/images/brand/university-logo.svg')
        ->and($validDef->seoDefaultImageUrl)->toBe('https://example.edu/assets/og-banner.jpg')
        ->and($validDef->email)->toBeNull()
        ->and($validDef->phone)->toBeNull();
});

it('executes zero database queries when resolving SiteDefinition', function () {
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    $resolver = app(SiteDefinitionContract::class);
    $resolver->forLocale('en');
    $resolver->forLocale('ar');
    $resolver->forLocale('fr');
    $resolver->current();

    expect($queries)->toBe([]);
});

it('renders Header, Hero, About, and Footer from SiteDefinition in English and Arabic with accessible branding and XSS escaping', function () {
    config([
        'website.branding.logo_url' => '/images/campus-crest.svg',
        'website.branding.logo_alt' => [
            'en' => 'University CampusHub Crest',
            'ar' => 'شعار منصة الحرم الجامعي',
        ],
    ]);

    // Homepage EN
    $homeEn = $this->withSession(['web_locale' => 'en'])->get('/');
    $homeEn->assertOk()
        ->assertSee('data-website-header-brand', false)
        ->assertSee('src="/images/campus-crest.svg"', false)
        ->assertSee('alt="University CampusHub Crest"', false)
        ->assertSee('data-website-footer-identity', false)
        ->assertSee('info@campushub.edu')
        ->assertSee('+1 (555) 010-2000')
        ->assertSee('100 University Avenue, Central Campus');

    // About page EN
    $aboutEn = $this->withSession(['web_locale' => 'en'])->get('/about');
    $aboutEn->assertOk()
        ->assertSee('University CampusHub')
        ->assertSee('Empowering Future Leaders')
        ->assertSee('Contact Information')
        ->assertSee('100 University Avenue, Central Campus')
        ->assertSee('Sunday – Thursday, 8:00 AM – 4:00 PM')
        ->assertSee('info@campushub.edu');

    // About page AR
    $aboutAr = $this->withSession(['web_locale' => 'ar'])->get('/about');
    $aboutAr->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('شعار منصة الحرم الجامعي')
        ->assertSee('منصة الحرم الجامعي')
        ->assertSee('تمكين قادة المستقبل')
        ->assertSee('معلومات التواصل')
        ->assertSee('100 شارع الجامعة، الحرم الجامعي المركزي')
        ->assertSee('الأحد – الخميس، 8:00 صباحاً – 4:00 مساءً');

    // XSS escaping check
    $xss = '<script>alert("site-xss")</script>';
    config([
        'website.identity.name' => ['en' => 'Uni '.$xss],
        'website.identity.tagline' => ['en' => 'Tag '.$xss],
        'website.contact.address' => ['en' => 'Addr '.$xss],
    ]);

    $xssResp = $this->withSession(['web_locale' => 'en'])->get('/about');
    $xssResp->assertOk()
        ->assertDontSee($xss, false)
        ->assertSee(htmlspecialchars($xss, ENT_QUOTES, 'UTF-8'), false);
});
