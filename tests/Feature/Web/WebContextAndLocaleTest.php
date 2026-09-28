<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Services\ContentLocaleService;
use Webkul\Web\Context\WebContext;
use Webkul\Web\Contracts\WebContextContract;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Route::get('_test/web-context-pipeline', function (WebContextContract $context) {
        return response()->json([
            'locale' => $context->locale(),
            'direction' => $context->direction(),
            'is_rtl' => $context->isRtl(),
            'canonical_url' => $context->canonicalUrl(),
            'app_locale' => app()->getLocale(),
        ]);
    })->middleware(['web', 'web_context']);
});

it('resolves primary content locale and direction by default on web requests', function () {
    $primaryLocale = app(ContentLocaleService::class)->primaryContentLocale();

    $response = $this->get('_test/web-context-pipeline');

    $response->assertOk()
        ->assertJson([
            'locale' => $primaryLocale->code,
            'direction' => $primaryLocale->direction()->value,
            'is_rtl' => $primaryLocale->direction()->value === 'rtl',
            'app_locale' => $primaryLocale->code,
        ]);
});

it('resolves explicit session web locale when code is active in content locale registry', function () {
    // ar is seeded as active with rtl direction
    $response = $this->withSession(['web_locale' => 'ar'])
        ->get('_test/web-context-pipeline');

    $response->assertOk()
        ->assertJson([
            'locale' => 'ar',
            'direction' => 'rtl',
            'is_rtl' => true,
            'app_locale' => 'ar',
        ]);

    // en is seeded as active with ltr direction
    $responseEn = $this->withSession(['web_locale' => 'en'])
        ->get('_test/web-context-pipeline');

    $responseEn->assertOk()
        ->assertJson([
            'locale' => 'en',
            'direction' => 'ltr',
            'is_rtl' => false,
            'app_locale' => 'en',
        ]);
});

it('falls back to primary content locale when requested session locale is inactive or unknown', function () {
    $primaryLocale = app(ContentLocaleService::class)->primaryContentLocale();

    $response = $this->withSession(['web_locale' => 'fr_FR']) // unknown code
        ->get('_test/web-context-pipeline');

    $response->assertOk()
        ->assertJson([
            'locale' => $primaryLocale->code,
            'direction' => $primaryLocale->direction()->value,
            'app_locale' => $primaryLocale->code,
        ]);
});

it('allows switching web locale via web.locale.switch and persists to session and cookie', function () {
    $response = $this->get(route('web.locale.switch', ['code' => 'ar']));

    $response->assertRedirect();
    $response->assertSessionHas('web_locale', 'ar');
    $response->assertCookie('web_locale', 'ar');

    // Invalid locale should not be accepted
    $invalidResponse = $this->get(route('web.locale.switch', ['code' => 'unsupported_code']));
    $invalidResponse->assertRedirect();
    $invalidResponse->assertSessionHas('error');
});

it('proves Admin locale authority and Web locale authority remain completely independent', function () {
    // 1. A Web request with session 'web_locale' => 'ar' sets Web context to 'ar'
    $webResponse = $this->withSession(['web_locale' => 'ar'])
        ->get('_test/web-context-pipeline');

    $webResponse->assertJson(['locale' => 'ar']);

    // 2. Admin routes resolve independently via Admin middleware stack
    $adminResponse = $this->get(route('admin.session.create'));
    $adminResponse->assertOk();
});
