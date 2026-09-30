<?php

namespace Tests\Feature\LostAndFound;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Contracts\PublicLostAndFoundReadContract;
use Webkul\LostAndFound\DataTransferObjects\PublicFoundItemData;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\LostAndFound\Models\FoundItemPrivateDetail;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Services\PublicLostAndFoundService;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class PublicLostAndFoundReadTest extends TestCase
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

    private function createCategory(string $code = 'electronics', bool $active = true): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code'       => $code.'_'.uniqid(),
            'is_active'  => $active,
            'sort_order' => 1,
        ]);
    }

    private function createItem(User $user, array $attributes = []): FoundItem
    {
        $ref = 'LF-'.strtoupper(Str::random(8));

        return FoundItem::create(array_merge([
            'public_reference'   => $ref,
            'logged_by_user_id'  => $user->id,
            'status'             => ItemStatus::REPORTED,
            'title'              => 'Found Blue Backpack',
            'public_description' => 'Blue canvas backpack found in the student lounge',
            'found_location'     => 'Student Union Building 2nd Floor',
            'found_at'           => Carbon::now()->subHours(2),
            'reported_at'        => Carbon::now()->subHours(1),
        ], $attributes));
    }

    public function test_resolves_public_lost_and_found_read_contract(): void
    {
        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        $this->assertInstanceOf(PublicLostAndFoundReadContract::class, $reader);
        $this->assertInstanceOf(PublicLostAndFoundService::class, $reader);
    }

    public function test_returns_only_public_safe_statuses(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $reportedItem = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'title'       => 'Reported Item',
        ]);

        $inCustodyItem = $this->createItem($user, [
            'status'      => ItemStatus::IN_CUSTODY,
            'category_id' => $cat->id,
            'title'       => 'In Custody Item',
        ]);

        $draftItem = $this->createItem($user, [
            'status'      => ItemStatus::DRAFT,
            'category_id' => $cat->id,
            'title'       => 'Draft Item',
        ]);

        $returnedItem = $this->createItem($user, [
            'status'      => ItemStatus::RETURNED,
            'category_id' => $cat->id,
            'title'       => 'Returned Item',
        ]);

        $disposedItem = $this->createItem($user, [
            'status'      => ItemStatus::DISPOSED,
            'category_id' => $cat->id,
            'title'       => 'Disposed Item',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(50);
        $references = array_column($items, 'reference');

        $this->assertContains($reportedItem->public_reference, $references);
        $this->assertContains($inCustodyItem->public_reference, $references);
        $this->assertNotContains($draftItem->public_reference, $references);
        $this->assertNotContains($returnedItem->public_reference, $references);
        $this->assertNotContains($disposedItem->public_reference, $references);
    }

    public function test_excludes_private_and_staff_only_fields(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $item = $this->createItem($user, [
            'status'      => ItemStatus::IN_CUSTODY,
            'category_id' => $cat->id,
        ]);

        $item->privateDetail()->create([
            'identifying_details' => 'Secret internal tag xyz-999',
            'serial_fragment'     => 'SN-SECRET-12345',
            'staff_notes'         => 'Suspected abandoned or dropped near staff room',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(10);

        $matched = null;
        foreach ($items as $dto) {
            if ($dto->reference === $item->public_reference) {
                $matched = $dto;
                break;
            }
        }

        $this->assertNotNull($matched);
        $this->assertInstanceOf(PublicFoundItemData::class, $matched);

        // Public fields are present
        $this->assertSame($item->public_reference, $matched->reference);
        $this->assertSame($item->title, $matched->title);
        $this->assertSame($item->public_description, $matched->description);
        $this->assertSame($item->found_location, $matched->foundLocation);
        $this->assertSame($cat->code, $matched->category);

        // Verify array serialization contains zero private / staff-only fields
        $array = $matched->toArray();
        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayNotHasKey('logged_by_user_id', $array);
        $this->assertArrayNotHasKey('category_id', $array);
        $this->assertArrayNotHasKey('approved_claim_id', $array);
        $this->assertArrayNotHasKey('current_custodian_user_id', $array);
        $this->assertArrayNotHasKey('current_storage_location', $array);
        $this->assertArrayNotHasKey('identifying_details', $array);
        $this->assertArrayNotHasKey('serial_fragment', $array);
        $this->assertArrayNotHasKey('staff_notes', $array);
        $this->assertArrayNotHasKey('status', $array);
    }

    public function test_excludes_staff_only_images_and_serves_public_safe_images(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $itemWithStaffImg = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'title'       => 'Item with staff-only image',
        ]);

        FoundItemImage::create([
            'found_item_id'      => $itemWithStaffImg->id,
            'created_by_user_id' => $user->id,
            'visibility'         => FoundItemImageVisibility::STAFF_ONLY,
            'storage_key'        => 'lost-found/items/private/secret.jpg',
            'mime_type'          => 'image/jpeg',
            'byte_size'          => 1024,
            'sort_order'         => 0,
        ]);

        $itemWithPublicImg = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'title'       => 'Item with public-safe image',
        ]);

        FoundItemImage::create([
            'found_item_id'      => $itemWithPublicImg->id,
            'created_by_user_id' => $user->id,
            'visibility'         => FoundItemImageVisibility::PUBLIC_SAFE,
            'storage_key'        => 'lost-found/items/public/photo.jpg',
            'mime_type'          => 'image/jpeg',
            'byte_size'          => 2048,
            'sort_order'         => 0,
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(50);

        $dtoStaff = collect($items)->firstWhere('reference', $itemWithStaffImg->public_reference);
        $dtoPublic = collect($items)->firstWhere('reference', $itemWithPublicImg->public_reference);

        $this->assertNotNull($dtoStaff);
        $this->assertFalse($dtoStaff->hasImage);
        $this->assertNull($dtoStaff->imageUrl);

        $this->assertNotNull($dtoPublic);
        $this->assertTrue($dtoPublic->hasImage);
        $this->assertNotNull($dtoPublic->imageUrl);
        $this->assertStringContainsString('lost-found/items/public/photo.jpg', $dtoPublic->imageUrl);
        $this->assertStringNotContainsString('private', $dtoPublic->imageUrl);
    }

    public function test_respects_and_clamps_query_limits(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        for ($i = 0; $i < 5; $i++) {
            $this->createItem($user, [
                'status'      => ItemStatus::REPORTED,
                'category_id' => $cat->id,
            ]);
        }

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);

        // Limit of 2
        $items2 = $reader->getRecentPublicFoundItems(2);
        $this->assertCount(2, $items2);

        // Clamping negative or zero to minimum 1
        $items0 = $reader->getRecentPublicFoundItems(0);
        $this->assertCount(1, $items0);

        // Clamping huge limits to max 24
        $itemsLarge = $reader->getRecentPublicFoundItems(100);
        $this->assertLessThanOrEqual(24, count($itemsLarge));
    }

    public function test_sorts_results_deterministically_by_found_at_desc(): void
    {
        $user = $this->createEmployee();
        $cat = $this->createCategory();

        $older = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'found_at'    => Carbon::now()->subDays(5),
            'title'       => 'Older Item',
        ]);

        $newer = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $cat->id,
            'found_at'    => Carbon::now()->subDays(1),
            'title'       => 'Newer Item',
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(10);

        $olderIndex = null;
        $newerIndex = null;

        foreach ($items as $idx => $dto) {
            if ($dto->reference === $older->public_reference) {
                $olderIndex = $idx;
            }
            if ($dto->reference === $newer->public_reference) {
                $newerIndex = $idx;
            }
        }

        $this->assertNotNull($olderIndex);
        $this->assertNotNull($newerIndex);
        $this->assertLessThan($olderIndex, $newerIndex, 'Newer item should appear before older item');
    }

    public function test_inactive_categories_return_null_category(): void
    {
        $user = $this->createEmployee();
        $inactiveCat = $this->createCategory('inactive_cat', false);

        $item = $this->createItem($user, [
            'status'      => ItemStatus::REPORTED,
            'category_id' => $inactiveCat->id,
        ]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(50);

        $matched = collect($items)->firstWhere('reference', $item->public_reference);
        $this->assertNotNull($matched);
        $this->assertNull($matched->category);
    }

    public function test_public_found_item_data_serialization_is_safe(): void
    {
        $now = Carbon::now();
        $dto = new PublicFoundItemData(
            reference: 'LF-TEST-1234',
            title: 'Sample Item',
            category: 'electronics',
            foundLocation: 'Lab 1',
            foundAt: $now,
            description: 'A black laptop',
            imageUrl: 'https://example.com/storage/photo.jpg',
            hasImage: true,
        );

        $json = json_encode($dto);
        $decoded = json_decode($json, true);

        $this->assertSame('LF-TEST-1234', $decoded['reference']);
        $this->assertSame('Sample Item', $decoded['title']);
        $this->assertSame('electronics', $decoded['category']);
        $this->assertSame('Lab 1', $decoded['found_location']);
        $this->assertSame($now->format('Y-m-d H:i:s'), $decoded['found_at']);
        $this->assertSame('A black laptop', $decoded['description']);
        $this->assertSame('https://example.com/storage/photo.jpg', $decoded['image_url']);
        $this->assertTrue($decoded['has_image']);

        // Assert strictly no other keys
        $expectedKeys = ['reference', 'title', 'category', 'found_location', 'found_at', 'description', 'image_url', 'has_image', 'additional_images'];
        sort($expectedKeys);
        $actualKeys = array_keys($decoded);
        sort($actualKeys);
        $this->assertSame($expectedKeys, $actualKeys);
    }

    public function test_never_returns_eloquent_models_or_internal_collections(): void
    {
        $user = $this->createEmployee();
        $this->createItem($user, ['status' => ItemStatus::REPORTED]);

        $reader = $this->app->make(PublicLostAndFoundReadContract::class);
        $items = $reader->getRecentPublicFoundItems(10);

        $this->assertIsArray($items);
        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertInstanceOf(PublicFoundItemData::class, $item);
            $this->assertNotInstanceOf(\Illuminate\Database\Eloquent\Model::class, $item);
            $this->assertNotInstanceOf(FoundItem::class, $item);
        }
    }
}
