<?php

namespace Tests\Feature\LostAndFound;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicCategoryData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchCriteria;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemSearchResult;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\LostAndFound\Models\FoundItemPrivateDetail;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class PublicLostAndFoundSearchAndDetailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('lost_found_private');
    }

    private function createEmployee(): User
    {
        $role = Role::create([
            'name'            => 'Role '.Str::random(8),
            'description'     => null,
            'permission_type' => 'all',
            'permissions'     => null,
        ]);

        return User::create([
            'name'     => 'Test Staff',
            'email'    => 'staff-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id'  => $role->id,
            'status'   => 1,
        ]);
    }

    private function createCategory(string $code = 'cat', bool $active = true, int $sortOrder = 1): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code'       => $code.'_'.uniqid(),
            'is_active'  => $active,
            'sort_order' => $sortOrder,
        ]);
    }

    private function createItem(User $user, array $attributes = []): FoundItem
    {
        $ref = 'LF-'.strtoupper(Str::random(8));

        return FoundItem::create(array_merge([
            'public_reference'   => $ref,
            'logged_by_user_id'  => $user->id,
            'status'             => ItemStatus::REPORTED,
            'title'              => 'Found Silver Laptop',
            'public_description' => '15-inch silver laptop found in classroom',
            'found_location'     => 'Engineering Building Room 204',
            'found_at'           => Carbon::now()->subHours(2),
            'reported_at'        => Carbon::now()->subHours(1),
        ], $attributes));
    }

    public function test_search_returns_only_public_safe_statuses(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $reported = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'title'       => 'Searchable Reported Bag',
        ]);

        $inCustody = $this->createItem($user, [
            'status'      => ItemStatus::IN_CUSTODY,
            'category_id' => $cat->id,
            'title'       => 'Searchable InCustody Wallet',
        ]);

        $draft = $this->createItem($user, [
            'status'      => ItemStatus::DRAFT,
            'category_id' => $cat->id,
            'title'       => 'Secret Draft Item',
        ]);

        $returned = $this->createItem($user, [
            'status'      => ItemStatus::RETURNED,
            'category_id' => $cat->id,
            'title'       => 'Secret Returned Phone',
        ]);

        $disposed = $this->createItem($user, [
            'status'      => ItemStatus::DISPOSED,
            'category_id' => $cat->id,
            'title'       => 'Secret Disposed Umbrella',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $result = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'Searchable'));

        $this->assertInstanceOf(PublicFoundItemSearchResult::class, $result);
        $references = array_map(fn (PublicFoundItemData $d) => $d->reference, $result->items);

        $this->assertContains($reported->public_reference, $references);
        $this->assertContains($inCustody->public_reference, $references);

        // Search for secret items should yield zero results
        $draftResult = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'Draft'));
        $this->assertEmpty($draftResult->items);

        $returnedResult = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'Returned'));
        $this->assertEmpty($returnedResult->items);

        $disposedResult = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'Disposed'));
        $this->assertEmpty($disposedResult->items);
    }

    public function test_search_matches_title_description_location_and_reference(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $item1 = $this->createItem($user, [
            'category_id'        => $cat->id,
            'title'              => 'Blue Hydroflask Bottle',
            'public_description' => 'Stainless steel 32oz bottle',
            'found_location'     => 'Gymnasium Court B',
        ]);

        $item2 = $this->createItem($user, [
            'category_id'        => $cat->id,
            'title'              => 'Black Leather Backpack',
            'public_description' => 'Contains notebook and pens',
            'found_location'     => 'Central Library Floor 3',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        // Match by title
        $resTitle = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'hydroflask'));
        $this->assertCount(1, $resTitle->items);
        $this->assertSame($item1->public_reference, $resTitle->items[0]->reference);

        // Match by description
        $resDesc = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'notebook'));
        $this->assertCount(1, $resDesc->items);
        $this->assertSame($item2->public_reference, $resDesc->items[0]->reference);

        // Match by location
        $resLoc = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'Gymnasium'));
        $this->assertCount(1, $resLoc->items);
        $this->assertSame($item1->public_reference, $resLoc->items[0]->reference);

        // Match by public reference
        $resRef = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: strtolower($item2->public_reference)));
        $this->assertCount(1, $resRef->items);
        $this->assertSame($item2->public_reference, $resRef->items[0]->reference);
    }

    public function test_search_never_matches_private_details_or_staff_notes(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $item = $this->createItem($user, [
            'category_id'        => $cat->id,
            'title'              => 'Plain Smartphone',
            'public_description' => 'Black smartphone with case',
            'found_location'     => 'Main Cafeteria',
        ]);

        $detail = new FoundItemPrivateDetail();
        $detail->found_item_id = $item->id;
        $detail->identifying_details = 'SUPER_CONFIDENTIAL_ENGRAVING_XYZ';
        $detail->serial_fragment = 'SN987654321';
        $detail->staff_notes = 'STUDENT_SECRET_NOTE_ABC';
        $detail->save();

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        $res1 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'SUPER_CONFIDENTIAL_ENGRAVING_XYZ'));
        $this->assertEmpty($res1->items);

        $res2 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'SN987654321'));
        $this->assertEmpty($res2->items);

        $res3 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: 'STUDENT_SECRET_NOTE_ABC'));
        $this->assertEmpty($res3->items);
    }

    public function test_search_filters_by_category(): void
    {
        $user = $this->createEmployee();
        $catElectronics = $this->createCategory('elec');
        $catClothing = $this->createCategory('cloth');

        $itemElec = $this->createItem($user, [
            'category_id' => $catElectronics->id,
            'title'       => 'Electronic Tablet',
        ]);

        $itemCloth = $this->createItem($user, [
            'category_id' => $catClothing->id,
            'title'       => 'Winter Jacket',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        $resElec = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(category: $catElectronics->code));
        $refsElec = array_map(fn ($i) => $i->reference, $resElec->items);
        $this->assertContains($itemElec->public_reference, $refsElec);
        $this->assertNotContains($itemCloth->public_reference, $refsElec);

        $resCloth = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(category: $catClothing->code));
        $refsCloth = array_map(fn ($i) => $i->reference, $resCloth->items);
        $this->assertContains($itemCloth->public_reference, $refsCloth);
        $this->assertNotContains($itemElec->public_reference, $refsCloth);
    }

    public function test_search_treats_sql_wildcards_literally(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $literalPercent = $this->createItem($user, [
            'category_id' => $cat->id,
            'title'       => 'Reward 100% Guaranteed',
        ]);

        $other = $this->createItem($user, [
            'category_id' => $cat->id,
            'title'       => 'Ordinary Blue Umbrella',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        // Search for '%' should only find the one with the literal percent sign
        $res = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: '%'));
        $refs = array_map(fn ($i) => $i->reference, $res->items);

        $this->assertContains($literalPercent->public_reference, $refs);
        $this->assertNotContains($other->public_reference, $refs);
    }

    public function test_search_pagination_is_bounded_and_calculates_pages(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        for ($i = 0; $i < 15; $i++) {
            $this->createItem($user, [
                'category_id' => $cat->id,
                'title'       => "Batch Item {$i}",
            ]);
        }

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        $page1 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(page: 1, perPage: 5));
        $this->assertSame(1, $page1->currentPage);
        $this->assertSame(5, $page1->perPage);
        $this->assertCount(5, $page1->items);
        $this->assertGreaterThanOrEqual(15, $page1->total);
        $this->assertGreaterThanOrEqual(3, $page1->lastPage);
        $this->assertTrue($page1->hasPages());
        $this->assertNull($page1->previousPage());
        $this->assertSame(2, $page1->nextPage());

        $page2 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(page: 2, perPage: 5));
        $this->assertSame(2, $page2->currentPage);
        $this->assertCount(5, $page2->items);
        $this->assertSame(1, $page2->previousPage());

        // Clamping check: perPage > 36 clamps to 36
        $clamped = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(perPage: 100));
        $this->assertSame(36, $clamped->perPage);
    }

    public function test_find_public_found_item_by_reference_normalizes_case(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $item = $this->createItem($user, [
            'category_id' => $cat->id,
            'title'       => 'Specific Test Item',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        // Lowercase query
        $foundLower = $reader->findPublicFoundItemByReference(strtolower($item->public_reference));
        $this->assertNotNull($foundLower);
        $this->assertSame($item->public_reference, $foundLower->reference);
        $this->assertSame('Specific Test Item', $foundLower->title);

        // Uppercase query
        $foundUpper = $reader->findPublicFoundItemByReference(strtoupper($item->public_reference));
        $this->assertNotNull($foundUpper);
        $this->assertSame($item->public_reference, $foundUpper->reference);
    }

    public function test_find_by_reference_enumeration_protection(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $draft = $this->createItem($user, [
            'status'      => ItemStatus::DRAFT,
            'category_id' => $cat->id,
        ]);

        $returned = $this->createItem($user, [
            'status'      => ItemStatus::RETURNED,
            'category_id' => $cat->id,
        ]);

        $disposed = $this->createItem($user, [
            'status'      => ItemStatus::DISPOSED,
            'category_id' => $cat->id,
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        // Non-existent reference returns null
        $this->assertNull($reader->findPublicFoundItemByReference('LF-NONEXISTENT'));

        // Non-public items return null (identical to non-existent)
        $this->assertNull($reader->findPublicFoundItemByReference($draft->public_reference));
        $this->assertNull($reader->findPublicFoundItemByReference($returned->public_reference));
        $this->assertNull($reader->findPublicFoundItemByReference($disposed->public_reference));
    }

    public function test_find_by_reference_includes_multiple_public_safe_images_and_excludes_staff_only(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $item = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
        ]);

        FoundItemImage::create([
            'found_item_id'      => $item->id,
            'created_by_user_id' => $user->id,
            'visibility'         => FoundItemImageVisibility::PUBLIC_SAFE,
            'storage_key'        => 'lost-found/items/public/img1.jpg',
            'mime_type'          => 'image/jpeg',
            'byte_size'          => 1024,
            'sort_order'         => 0,
        ]);

        FoundItemImage::create([
            'found_item_id'      => $item->id,
            'created_by_user_id' => $user->id,
            'visibility'         => FoundItemImageVisibility::PUBLIC_SAFE,
            'storage_key'        => 'lost-found/items/public/img2.jpg',
            'mime_type'          => 'image/jpeg',
            'byte_size'          => 2048,
            'sort_order'         => 1,
        ]);

        FoundItemImage::create([
            'found_item_id'      => $item->id,
            'created_by_user_id' => $user->id,
            'visibility'         => FoundItemImageVisibility::STAFF_ONLY,
            'storage_key'        => 'lost-found/items/private/secret.jpg',
            'mime_type'          => 'image/jpeg',
            'byte_size'          => 4096,
            'sort_order'         => 2,
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $dto = $reader->findPublicFoundItemByReference($item->public_reference);

        $this->assertNotNull($dto);
        $this->assertTrue($dto->hasImage);
        $this->assertNotNull($dto->imageUrl);
        $this->assertStringContainsString('lost-found/items/public/img1.jpg', $dto->imageUrl);

        // Additional images list
        $this->assertCount(2, $dto->additionalImages);
        $this->assertStringContainsString('lost-found/items/public/img1.jpg', $dto->additionalImages[0]);
        $this->assertStringContainsString('lost-found/items/public/img2.jpg', $dto->additionalImages[1]);

        foreach ($dto->additionalImages as $img) {
            $this->assertStringNotContainsString('secret.jpg', $img);
            $this->assertStringNotContainsString('private', $img);
        }
    }

    public function test_get_public_categories_returns_only_active_categories_sorted(): void
    {
        $active1 = $this->createCategory('active_b', true, 20);
        $active2 = $this->createCategory('active_a', true, 10);
        $inactive = $this->createCategory('inactive_z', false, 5);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $categories = $reader->getPublicCategories();

        $this->assertIsArray($categories);
        $codes = array_map(fn (PublicCategoryData $c) => $c->code, $categories);

        $this->assertContains($active1->code, $codes);
        $this->assertContains($active2->code, $codes);
        $this->assertNotContains($inactive->code, $codes);

        // Verify sorting by sort_order
        $indexA = array_search($active2->code, $codes, true);
        $indexB = array_search($active1->code, $codes, true);
        $this->assertLessThan($indexB, $indexA, 'active_a with sort_order 10 should precede active_b with sort_order 20');
    }

    public function test_search_is_safe_against_sql_injection_fragments(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();
        $this->createItem($user, ['category_id' => $cat->id, 'title' => 'Safe Test Item']);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        $res1 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(query: "' OR 1=1 --"));
        $this->assertEmpty($res1->items);

        $res2 = $reader->searchPublicFoundItems(new PublicFoundItemSearchCriteria(category: "' OR '1'='1"));
        $this->assertEmpty($res2->items);
    }

    public function test_new_dtos_serialize_safely_with_exact_keys(): void
    {
        $catDto = new PublicCategoryData(code: 'electronics', name: 'Electronics');
        $catArray = $catDto->toArray();
        $this->assertSame(['code' => 'electronics', 'name' => 'Electronics'], $catArray);
        $this->assertSame($catArray, json_decode(json_encode($catDto), true));

        $itemDto = new PublicFoundItemData(reference: 'LF-1', title: 'Test');
        $resultDto = new PublicFoundItemSearchResult(
            items: [$itemDto],
            total: 1,
            perPage: 12,
            currentPage: 1,
            lastPage: 1,
        );

        $resArray = $resultDto->toArray();
        $expectedResultKeys = ['items', 'total', 'per_page', 'current_page', 'last_page'];
        $this->assertSame($expectedResultKeys, array_keys($resArray));
        $this->assertSame($resArray, json_decode(json_encode($resultDto), true));
    }
}
