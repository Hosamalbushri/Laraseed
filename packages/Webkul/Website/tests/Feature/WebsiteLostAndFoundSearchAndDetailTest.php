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

class TestPublicLostAndFoundSearchDetailReader implements PublicLostAndFoundReadContract
{
    /**
     * @param list<PublicFoundItemData> $items
     * @param list<PublicCategoryData> $categories
     */
    public function __construct(
        public array $items = [],
        public array $categories = [],
    ) {}

    public function getRecentPublicFoundItems(int $limit = 6): array
    {
        return array_slice($this->items, 0, $limit);
    }

    public function searchPublicFoundItems(PublicFoundItemSearchCriteria $criteria): PublicFoundItemSearchResult
    {
        $filtered = $this->items;

        if ($criteria->category !== null) {
            $filtered = array_values(array_filter(
                $filtered,
                fn (PublicFoundItemData $item) => $item->category === $criteria->category
            ));
        }

        if ($criteria->query !== null) {
            $q = mb_strtolower($criteria->query);
            $filtered = array_values(array_filter(
                $filtered,
                fn (PublicFoundItemData $item) => str_contains(mb_strtolower($item->title), $q)
                    || str_contains(mb_strtolower($item->description ?? ''), $q)
                    || str_contains(mb_strtolower($item->reference), $q)
            ));
        }

        $total = count($filtered);
        $lastPage = max(1, (int) ceil($total / $criteria->perPage));
        $currentPage = min($criteria->page, $lastPage);
        $offset = ($currentPage - 1) * $criteria->perPage;
        $pageItems = array_slice($filtered, $offset, $criteria->perPage);

        return new PublicFoundItemSearchResult(
            items: $pageItems,
            total: $total,
            perPage: $criteria->perPage,
            currentPage: $currentPage,
            lastPage: $lastPage,
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

beforeEach(function () {
    $this->app->register(\Webkul\Website\Integrations\LostAndFound\WebsiteLostAndFoundServiceProvider::class);
});

it('registers navigation items for Lost & Found directory in header and footer', function () {
    $navigation = app(NavigationRegistryContract::class);

    $headerItems = $navigation->getItems('header');
    expect($headerItems->pluck('id')->all())->toContain('website_lost_found');

    $footerItems = $navigation->getItems('footer');
    expect($footerItems->pluck('id')->all())->toContain('website_footer_lost_found');
});

it('renders the public lost and found search page with items and categories', function () {
    $now = Carbon::parse('2026-09-28 10:00:00');
    $dummyItem = new PublicFoundItemData(
        reference: 'LF-SRCH-0001',
        title: 'Graphing Calculator TI-84',
        category: 'electronics',
        foundLocation: 'Science Hall 302',
        foundAt: $now,
        description: 'Black calculator with blue slide case',
        imageUrl: 'https://example.com/calculator.jpg',
        hasImage: true,
    );

    $category = new PublicCategoryData(
        code: 'electronics',
        name: 'Electronics & Gadgets',
    );

    $reader = new TestPublicLostAndFoundSearchDetailReader([$dummyItem], [$category]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found');

    $response->assertOk()
        ->assertSee('Campus Lost & Found')
        ->assertSee('LF-SRCH-0001')
        ->assertSee('Graphing Calculator TI-84')
        ->assertSee('electronics')
        ->assertSee('Science Hall 302')
        ->assertSee('2026-09-28')
        ->assertSee('Black calculator with blue slide case')
        ->assertSee('https://example.com/calculator.jpg')
        ->assertSee('Electronics & Gadgets')
        ->assertSee('1 items found');
});

it('filters search results by query and preserves query in search input', function () {
    $item1 = new PublicFoundItemData(reference: 'LF-A1', title: 'Blue Hydroflask');
    $item2 = new PublicFoundItemData(reference: 'LF-A2', title: 'Red Backpack');

    $reader = new TestPublicLostAndFoundSearchDetailReader([$item1, $item2]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found?q=Hydroflask');

    $response->assertOk()
        ->assertSee('LF-A1')
        ->assertSee('Blue Hydroflask')
        ->assertDontSee('LF-A2')
        ->assertDontSee('Red Backpack')
        ->assertSee('value="Hydroflask"', false)
        ->assertSee('Clear Filters');
});

it('renders the deliberate empty state when no items match the search query', function () {
    $reader = new TestPublicLostAndFoundSearchDetailReader([]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found?q=NonExistentItem');

    $response->assertOk()
        ->assertSee('No Items Found')
        ->assertSee('No found items matched your search criteria')
        ->assertSee('Clear Filters');
});

it('renders pagination links and preserves query and category parameters', function () {
    $items = [];
    for ($i = 1; $i <= 25; $i++) {
        $items[] = new PublicFoundItemData(
            reference: sprintf('LF-PAGE-%02d', $i),
            title: "Numbered Item {$i}",
            category: 'books',
        );
    }

    $reader = new TestPublicLostAndFoundSearchDetailReader($items);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found?category=books&page=1');

    $response->assertOk()
        ->assertSee('LF-PAGE-01')
        ->assertSee('LF-PAGE-12')
        ->assertDontSee('LF-PAGE-13')
        ->assertSee('Page 1 of 3')
        ->assertSee('Next')
        ->assertSee('page=2');
});

it('renders public item detail page with gallery and claim information', function () {
    $now = Carbon::parse('2026-09-28 15:30:00');
    $item = new PublicFoundItemData(
        reference: 'LF-DET-5555',
        title: 'Sony Wireless Headphones',
        category: 'electronics',
        foundLocation: 'Library 2nd Floor Quiet Zone',
        foundAt: $now,
        description: 'Black over-ear headphones in protective case',
        imageUrl: 'https://example.com/headphones1.jpg',
        hasImage: true,
        additionalImages: [
            'https://example.com/headphones1.jpg',
            'https://example.com/headphones2.jpg',
        ],
    );

    $reader = new TestPublicLostAndFoundSearchDetailReader([$item]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-DET-5555');

    $response->assertOk()
        ->assertSee('LF-DET-5555')
        ->assertSee('Sony Wireless Headphones')
        ->assertSee('electronics')
        ->assertSee('Library 2nd Floor Quiet Zone')
        ->assertSee('2026-09-28')
        ->assertSee('Black over-ear headphones in protective case')
        ->assertSee('https://example.com/headphones1.jpg')
        ->assertSee('https://example.com/headphones2.jpg')
        ->assertSee('Is this your item?')
        ->assertSee('Claim via Student Portal')
        ->assertSee('Campus Security Office');
});

it('returns 404 for non-existent item references enforcing enumeration protection', function () {
    $reader = new TestPublicLostAndFoundSearchDetailReader([]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $response = $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-NOT-FOUND');

    $response->assertNotFound();
});

it('never leaks internal columns or private fields on search or detail page', function () {
    $item = new PublicFoundItemData(
        reference: 'LF-PRIV-0001',
        title: 'Keys with Blue Lanyard',
        category: 'personal',
        foundLocation: 'Cafeteria',
        foundAt: Carbon::now(),
        description: 'Set of three keys on lanyard',
    );

    $reader = new TestPublicLostAndFoundSearchDetailReader([$item]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    // Check search page
    $responseSearch = $this->withSession(['web_locale' => 'en'])->get('/lost-found');
    $responseSearch->assertOk()
        ->assertDontSee('logged_by_user_id')
        ->assertDontSee('current_storage_location')
        ->assertDontSee('current_custodian_user_id')
        ->assertDontSee('identifying_details')
        ->assertDontSee('serial_fragment')
        ->assertDontSee('staff_notes')
        ->assertDontSee('approved_claim_id');

    // Check detail page
    $responseDetail = $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-PRIV-0001');
    $responseDetail->assertOk()
        ->assertDontSee('logged_by_user_id')
        ->assertDontSee('current_storage_location')
        ->assertDontSee('current_custodian_user_id')
        ->assertDontSee('identifying_details')
        ->assertDontSee('serial_fragment')
        ->assertDontSee('staff_notes')
        ->assertDontSee('approved_claim_id');
});

it('renders arabic RTL layout on search and detail views', function () {
    $now = Carbon::parse('2026-09-28 11:00:00');
    $item = new PublicFoundItemData(
        reference: 'LF-AR-0001',
        title: 'محفظة جلدية سوداء',
        category: 'شخصي',
        foundLocation: 'مبنى كلية العلوم',
        foundAt: $now,
        description: 'محفظة تحتوي على بطاقات متنوعة',
    );

    $category = new PublicCategoryData(code: 'personal', name: 'مقتنيات شخصية');

    $reader = new TestPublicLostAndFoundSearchDetailReader([$item], [$category]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    // Search view Arabic
    $responseSearch = $this->withSession(['web_locale' => 'ar'])->get('/lost-found');
    $responseSearch->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('المفقودات والموجودات في الحرم الجامعي')
        ->assertSee('LF-AR-0001')
        ->assertSee('محفظة جلدية سوداء');

    // Detail view Arabic
    $responseDetail = $this->withSession(['web_locale' => 'ar'])->get('/lost-found/LF-AR-0001');
    $responseDetail->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('محفظة جلدية سوداء')
        ->assertSee('هل هذا المقتنى يخصك؟')
        ->assertSee('المطالبة عبر بوابة الطلاب');
});

it('escapes user search queries and database XSS payloads in rendered HTML', function () {
    $xssPayload = '<script>alert("xss")</script>';
    $maliciousItem = new PublicFoundItemData(
        reference: 'LF-XSS-0001',
        title: 'Item '.$xssPayload,
        description: 'Desc '.$xssPayload,
        foundLocation: 'Loc '.$xssPayload,
    );

    $reader = new TestPublicLostAndFoundSearchDetailReader([$maliciousItem]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    // Search query XSS
    $responseQuery = $this->withSession(['web_locale' => 'en'])->get('/lost-found?q='.urlencode($xssPayload));
    $responseQuery->assertOk()
        ->assertDontSee($xssPayload, false)
        ->assertSee(htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8'), false);

    // Detail page database content XSS
    $responseDetail = $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-XSS-0001');
    $responseDetail->assertOk()
        ->assertDontSee($xssPayload, false)
        ->assertSee(htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8'), false);
});

it('distinguishes unfiltered empty directory state from filtered zero-results state', function () {
    $reader = new TestPublicLostAndFoundSearchDetailReader([]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $unfiltered = $this->withSession(['web_locale' => 'en'])->get('/lost-found');
    $unfiltered->assertOk()
        ->assertSee('No Unclaimed Items')
        ->assertDontSee('Clear Filters');

    $filtered = $this->withSession(['web_locale' => 'en'])->get('/lost-found?q=missing');
    $filtered->assertOk()
        ->assertSee('No Items Found')
        ->assertSee('Clear Filters');
});

it('confirms Website search and detail controllers execute zero queries against lost_found tables', function () {
    $executedQueries = [];
    DB::listen(function ($query) use (&$executedQueries) {
        $executedQueries[] = strtolower($query->sql);
    });

    $item = new PublicFoundItemData(
        reference: 'LF-NOSQL-01',
        title: 'Zero SQL Test Item',
    );

    $reader = new TestPublicLostAndFoundSearchDetailReader([$item]);
    $this->app->instance(PublicLostAndFoundReadContract::class, $reader);

    $this->withSession(['web_locale' => 'en'])->get('/lost-found')->assertOk();
    $this->withSession(['web_locale' => 'en'])->get('/lost-found/LF-NOSQL-01')->assertOk();

    $lfQueries = array_filter($executedQueries, fn (string $sql) => str_contains($sql, 'lost_found_'));
    expect($lfQueries)->toBe([]);
});
