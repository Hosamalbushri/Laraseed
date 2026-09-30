<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithOptionalPackageComposition;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\DataTransferObjects\PublicCategoryData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchResult;
use Webkul\Web\Contracts\SectionRegistryContract;

uses(DatabaseTransactions::class, InteractsWithOptionalPackageComposition::class);

class FakePublicLostAndFoundReader implements PublicLostAndFoundReadContract
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

beforeEach(function () {
    $this->app->register(\Webkul\Website\Integrations\LostAndFound\WebsiteLostAndFoundServiceProvider::class);
});

it('registers the website_lost_found section when LostAndFound is composed', function () {
    $sectionRegistry = app(SectionRegistryContract::class);
    $sections = $sectionRegistry->getSections('home');

    expect($sections->pluck('key')->all())->toContain('website_lost_found');

    $section = $sections->firstWhere('key', 'website_lost_found');
    expect($section['order'])->toBe(25)
        ->and($section['view'])->toBe('website::sections.lost-found');
});

it('renders public DTO items on the website homepage', function () {
    $now = Carbon::parse('2026-09-28 14:00:00');
    $dummyDto = new PublicFoundItemData(
        reference: 'LF-PUB-9999',
        title: 'Silver Metallic Water Bottle',
        category: 'accessories',
        foundLocation: 'Science Building Room 101',
        foundAt: $now,
        description: 'Insulated 750ml bottle with university crest',
        imageUrl: null,
        hasImage: false,
    );

    $this->app->instance(PublicLostAndFoundReadContract::class, new FakePublicLostAndFoundReader([$dummyDto]));

    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertSee('website-lost-found', false)
        ->assertSee('LF-PUB-9999')
        ->assertSee('Silver Metallic Water Bottle')
        ->assertSee('accessories')
        ->assertSee('Science Building Room 101')
        ->assertSee('2026-09-28')
        ->assertSee('Insulated 750ml bottle with university crest');
});

it('never leaks private or staff-only data into the rendered HTML', function () {
    $dummyDto = new PublicFoundItemData(
        reference: 'LF-SAFE-0001',
        title: 'Black Umbrella',
        category: 'personal',
        foundLocation: 'Library Entrance',
        foundAt: Carbon::now(),
        description: 'Standard foldable umbrella',
    );

    $this->app->instance(PublicLostAndFoundReadContract::class, new FakePublicLostAndFoundReader([$dummyDto]));

    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertDontSee('logged_by_user_id')
        ->assertDontSee('staff_notes')
        ->assertDontSee('identifying_details')
        ->assertDontSee('serial_fragment')
        ->assertDontSee('current_storage_location')
        ->assertDontSee('current_custodian_user_id')
        ->assertDontSee('approved_claim_id');
});

it('renders the deliberate empty state when zero found items exist', function () {
    $this->app->instance(PublicLostAndFoundReadContract::class, new FakePublicLostAndFoundReader([]));

    $response = $this->withSession(['web_locale' => 'en'])->get('/');

    $response->assertOk()
        ->assertSee('website-lost-found__empty', false)
        ->assertSee('No Unclaimed Items')
        ->assertSee('There are currently no unclaimed items listed across campus facilities.');
});

it('renders arabic RTL on lost and found website section', function () {
    $now = Carbon::parse('2026-09-28 14:00:00');
    $dummyDto = new PublicFoundItemData(
        reference: 'LF-PUB-8888',
        title: 'محفظة جلدية بنية',
        category: 'شخصي',
        foundLocation: 'بهو كلية الهندسة',
        foundAt: $now,
        description: 'محفظة نقود تحتوي على بطاقات دراسية',
    );

    $this->app->instance(PublicLostAndFoundReadContract::class, new FakePublicLostAndFoundReader([$dummyDto]));

    $response = $this->withSession(['web_locale' => 'ar'])->get('/');

    $response->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('المقتنيات التي عُثر عليها حديثاً')
        ->assertSee('LF-PUB-8888')
        ->assertSee('محفظة جلدية بنية')
        ->assertSee('بهو كلية الهندسة');
});

it('confirms Website presentation queries zero LostAndFound database tables directly', function () {
    $executedQueries = [];
    DB::listen(function ($query) use (&$executedQueries) {
        $executedQueries[] = strtolower($query->sql);
    });

    // Provide a contract implementation that does not query DB
    $this->app->instance(PublicLostAndFoundReadContract::class, new FakePublicLostAndFoundReader([
        new PublicFoundItemData(
            reference: 'LF-NO-SQL-1',
            title: 'Non-SQL Test Item',
        ),
    ]));

    $response = $this->withSession(['web_locale' => 'en'])->get('/');
    $response->assertOk();

    // Verify zero queries against lost_found_* tables were initiated by Website views/controllers
    $lostFoundQueries = array_filter(
        $executedQueries,
        fn (string $sql) => str_contains($sql, 'lost_found_')
    );

    expect($lostFoundQueries)->toBe([]);
});
