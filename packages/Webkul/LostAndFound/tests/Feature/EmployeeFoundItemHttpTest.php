<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class EmployeeFoundItemHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('lost_found_private');
        Storage::fake('public');
    }

    protected function createCategory(): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code' => 'cat-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createSuperAdminUser(): User
    {
        $role = Role::create([
            'name' => 'Admin Role '.Str::random(4),
            'permission_type' => 'all',
        ]);

        return User::create([
            'name' => 'Admin '.Str::random(6),
            'email' => 'admin-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function createEmployeeWithPermissions(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Staff Role '.Str::random(4),
            'permission_type' => 'custom',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Staff '.Str::random(6),
            'email' => 'staff-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(8),
            'name' => 'Student '.Str::random(6),
            'password' => Hash::make('password'),
        ]);
    }

    public function test_authenticated_employee_with_permission_can_create_found_item(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.create']);
        $category = $this->createCategory();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Keys',
                'description' => 'Keyring found near library',
                'found_at' => now()->toDateString(),
                'found_location' => 'Main Library',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Found Keys')
            ->assertJsonPath('data.status', ItemStatus::REPORTED->value);

        $itemId = $response->json('data.id');
        $this->assertDatabaseHas('lost_found_items', [
            'id' => $itemId,
            'logged_by_user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ItemStatus::REPORTED->value,
        ]);
    }

    public function test_unauthenticated_request_returns_redirect_or_401(): void
    {
        $category = $this->createCategory();

        $response = $this->postJson(route('admin.lost_found.items.store'), [
            'category_id' => $category->id,
            'title' => 'Found Keys',
        ]);

        $this->assertTrue(in_array($response->status(), [302, 401], true));
    }

    public function test_student_guard_user_cannot_access_employee_endpoint(): void
    {
        $student = $this->createStudent();
        $category = $this->createCategory();

        $response = $this->actingAs($student, 'student')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Keys',
            ]);

        $this->assertTrue(in_array($response->status(), [302, 401, 403], true));
    }

    public function test_employee_without_permission_is_denied(): void
    {
        $user = $this->createEmployeeWithPermissions(['unrelated.permission']);
        $category = $this->createCategory();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Keys',
                'description' => 'Keyring found near library',
                'found_at' => now()->toDateString(),
                'found_location' => 'Main Library',
            ]);

        $this->assertTrue(in_array($response->status(), [401, 403], true));
        $this->assertDatabaseMissing('lost_found_items', [
            'title' => 'Found Keys',
        ]);
    }

    public function test_unrelated_permission_cannot_access_operations(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.view']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'View Only Item',
            'found_at' => now(),
            'found_location' => 'Hallway',
            'status' => ItemStatus::REPORTED,
        ]);

        // Attempt create
        $createResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'New Item',
                'found_at' => now()->toDateString(),
                'found_location' => 'Hallway',
            ]);
        $this->assertTrue(in_array($createResp->status(), [401, 403], true));

        // Attempt update
        $updateResp = $this->actingAs($user, 'user')
            ->putJson(route('admin.lost_found.items.update', $item->id), [
                'title' => 'Modified Title',
            ]);
        $this->assertTrue(in_array($updateResp->status(), [401, 403], true));

        // Attempt image upload
        $imageResp = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.images.store', $item->id), [
                'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
                'image' => UploadedFile::fake()->image('test.jpg', 200, 200),
            ]);
        $this->assertTrue(in_array($imageResp->status(), [401, 403], true));
    }

    public function test_logged_by_user_id_spoofing_in_payload_is_ignored(): void
    {
        $user1 = $this->createEmployeeWithPermissions(['lost_found.items.create']);
        $user2 = $this->createSuperAdminUser();
        $category = $this->createCategory();

        $response = $this->actingAs($user1, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Backpack',
                'found_at' => now()->toDateString(),
                'found_location' => 'Cafeteria',
                'logged_by_user_id' => $user2->id,
            ]);

        $response->assertStatus(201);
        $itemId = $response->json('data.id');

        $item = FoundItem::findOrFail($itemId);
        $this->assertEquals($user1->id, $item->logged_by_user_id);
    }

    public function test_status_spoofing_in_creation_payload_is_ignored(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.create']);
        $category = $this->createCategory();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Wallet',
                'found_at' => now()->toDateString(),
                'found_location' => 'Gym',
                'status' => ItemStatus::RETURNED->value,
            ]);

        $response->assertStatus(201);
        $itemId = $response->json('data.id');

        $item = FoundItem::findOrFail($itemId);
        $this->assertEquals(ItemStatus::REPORTED, $item->status);
    }

    public function test_custody_projection_fields_in_creation_payload_are_ignored(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.create']);
        $category = $this->createCategory();

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.store'), [
                'category_id' => $category->id,
                'title' => 'Found Phone',
                'found_at' => now()->toDateString(),
                'found_location' => 'Lab 3',
                'current_custodian_user_id' => $user->id,
                'current_storage_location' => 'Locker 99',
            ]);

        $response->assertStatus(201);
        $itemId = $response->json('data.id');

        $item = FoundItem::findOrFail($itemId);
        $this->assertNull($item->current_custodian_user_id);
        $this->assertNull($item->current_storage_location);
    }

    public function test_authenticated_employee_with_permission_can_update_found_item(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Original Title',
            'public_description' => 'Original Description',
            'found_at' => now(),
            'found_location' => 'Original Location',
            'status' => ItemStatus::REPORTED,
        ]);

        $response = $this->actingAs($user, 'user')
            ->putJson(route('admin.lost_found.items.update', $item->id), [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('lost_found_items', [
            'id' => $item->id,
            'title' => 'Updated Title',
            'public_description' => 'Updated Description',
        ]);
    }

    public function test_update_protected_workflow_fields_are_ignored_or_rejected(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-REF-12345',
            'title' => 'Test Item',
            'found_at' => now(),
            'found_location' => 'Lab',
            'status' => ItemStatus::REPORTED,
        ]);

        $response = $this->actingAs($user, 'user')
            ->putJson(route('admin.lost_found.items.update', $item->id), [
                'title' => 'Mod Title',
                'public_reference' => 'SPOOFED-REF',
            ]);

        $item->refresh();
        $this->assertEquals('FI-REF-12345', $item->public_reference);
    }

    public function test_authenticated_employee_can_upload_public_safe_image(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Item For Image',
            'found_at' => now(),
            'found_location' => 'Library',
            'status' => ItemStatus::REPORTED,
        ]);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.images.store', $item->id), [
                'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
                'image' => UploadedFile::fake()->image('public_item.jpg', 300, 300),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visibility', FoundItemImageVisibility::PUBLIC_SAFE->value);

        $this->assertNotEmpty(Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('lost_found_item_images', [
            'found_item_id' => $item->id,
            'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
        ]);
    }

    public function test_authenticated_employee_can_upload_staff_only_image(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Item For Private Image',
            'found_at' => now(),
            'found_location' => 'Office',
            'status' => ItemStatus::REPORTED,
        ]);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.images.store', $item->id), [
                'visibility' => FoundItemImageVisibility::STAFF_ONLY->value,
                'image' => UploadedFile::fake()->image('private_item.jpg', 300, 300),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visibility', FoundItemImageVisibility::STAFF_ONLY->value);

        $this->assertNotEmpty(Storage::disk('lost_found_private')->allFiles());
        $this->assertDatabaseHas('lost_found_item_images', [
            'found_item_id' => $item->id,
            'visibility' => FoundItemImageVisibility::STAFF_ONLY->value,
        ]);
    }

    public function test_image_upload_with_invalid_type_or_oversize_is_rejected(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Item For Invalid Image',
            'found_at' => now(),
            'found_location' => 'Office',
            'status' => ItemStatus::REPORTED,
        ]);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.images.store', $item->id), [
                'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
                'image' => UploadedFile::fake()->create('huge.jpg', 3000, 'image/jpeg'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_first_claim_freeze_blocks_image_upload(): void
    {
        $user = $this->createEmployeeWithPermissions(['lost_found.items.edit']);
        $student = $this->createStudent();
        $category = $this->createCategory();
        $item = FoundItem::create([
            'category_id' => $category->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Claimed Item',
            'found_at' => now(),
            'found_location' => 'Auditorium',
            'status' => ItemStatus::REPORTED,
        ]);

        LostFoundClaim::create([
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => ClaimStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('admin.lost_found.items.images.store', $item->id), [
                'visibility' => FoundItemImageVisibility::PUBLIC_SAFE->value,
                'image' => UploadedFile::fake()->image('late_image.jpg', 300, 300),
            ]);

        $this->assertNotEquals(201, $response->status());
        $this->assertDatabaseMissing('lost_found_item_images', [
            'found_item_id' => $item->id,
        ]);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
}
