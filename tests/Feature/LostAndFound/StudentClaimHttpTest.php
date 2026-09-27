<?php

namespace Tests\Feature\LostAndFound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\LostFoundCategory;
use Webkul\LostAndFound\Models\LostFoundClaim;
use Webkul\Student\Models\Student;
use Webkul\User\Models\User;

class StudentClaimHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('lost_found_private');
        Storage::fake('public');
    }

    protected function createStudent(): Student
    {
        return Student::create([
            'university_card_number' => 'STU-'.Str::random(8),
            'name' => 'Student '.Str::random(6),
            'password' => Hash::make('password'),
        ]);
    }

    protected function createCategory(): LostFoundCategory
    {
        return LostFoundCategory::create([
            'code' => 'cat-'.Str::random(6),
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    protected function createUser(): User
    {
        return User::create([
            'name' => 'Employee '.Str::random(6),
            'email' => 'employee-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'status' => 1,
        ]);
    }

    protected function createFoundItem(User $user, LostFoundCategory $category): FoundItem
    {
        return FoundItem::create([
            'category_id' => $category->id,
            'finder_user_id' => $user->id,
            'logged_by_user_id' => $user->id,
            'public_reference' => 'FI-'.strtoupper(Str::random(10)),
            'title' => 'Found Laptop '.Str::random(4),
            'description' => 'Silver laptop found in Hall A',
            'found_at' => now(),
            'found_location' => 'Hall A',
            'status' => ItemStatus::REPORTED,
        ]);
    }

    public function test_authenticated_student_can_submit_claim_via_http(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
                'statement' => 'This is my laptop.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', ClaimStatus::SUBMITTED->value);

        $claimId = $response->json('data.id');
        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claimId,
            'found_item_id' => $item->id,
            'claimant_student_id' => $student->id,
            'status' => ClaimStatus::SUBMITTED->value,
        ]);
    }

    public function test_claimant_id_spoofing_in_payload_is_ignored(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $response = $this->actingAs($student1, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
                'claimant_student_id' => $student2->id,
                'student_id' => $student2->id,
            ]);

        $response->assertStatus(201);
        $claimId = $response->json('data.id');

        $claim = LostFoundClaim::findOrFail($claimId);
        $this->assertEquals($student1->id, $claim->claimant_student_id);
    }

    public function test_unauthenticated_json_request_returns_401(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $response = $this->postJson(route('shop.student.lost_found.claims.store'), [
            'found_item_id' => $item->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_html_request_redirects(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $response = $this->post(route('shop.student.lost_found.claims.store'), [
            'found_item_id' => $item->id,
        ]);

        $response->assertStatus(302);
    }

    public function test_wrong_guard_user_cannot_submit_claim(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $response = $this->actingAs($user, 'user')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);

        $response->assertStatus(401);
    }

    public function test_duplicate_claim_by_same_student_is_rejected(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ])
            ->assertStatus(201);

        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);

        $this->assertNotEquals(201, $response->status());
        $this->assertEquals(1, LostFoundClaim::where('found_item_id', $item->id)->where('claimant_student_id', $student->id)->count());
    }

    public function test_authenticated_student_can_add_textual_evidence_to_own_claim(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.evidence.store', $claimId), [
                'type' => EvidenceType::MARKING_DETAIL->value,
                'content' => 'Initials HB engraved on bottom right corner.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', EvidenceType::MARKING_DETAIL->value);

        $this->assertDatabaseHas('lost_found_claim_evidence', [
            'claim_id' => $claimId,
            'evidence_type' => EvidenceType::MARKING_DETAIL->value,
        ]);
    }

    public function test_cross_student_text_evidence_returns_404_existence_hiding(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student1, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student2, 'student')
            ->postJson(route('shop.student.lost_found.claims.evidence.store', $claimId), [
                'type' => EvidenceType::MARKING_DETAIL->value,
                'content' => 'Malicious evidence injection',
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('lost_found_claim_evidence', [
            'claim_id' => $claimId,
        ]);
    }

    public function test_authenticated_student_can_upload_image_evidence_to_own_claim(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.images.store', $claimId), [
                'image' => UploadedFile::fake()->image('receipt.jpg', 400, 400),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', EvidenceType::IMAGE_ATTACHMENT->value);

        $this->assertNotEmpty(Storage::disk('lost_found_private')->allFiles());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_image_exceeding_configured_byte_limit_is_rejected(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        // 3000 KB > 2048 KB limit
        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.images.store', $claimId), [
                'image' => UploadedFile::fake()->create('huge.jpg', 3000, 'image/jpeg'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseMissing('lost_found_claim_evidence', [
            'claim_id' => $claimId,
            'evidence_type' => EvidenceType::IMAGE_ATTACHMENT->value,
        ]);
        $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
    }

    public function test_cross_student_image_evidence_returns_404_existence_hiding(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student1, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student2, 'student')
            ->postJson(route('shop.student.lost_found.claims.images.store', $claimId), [
                'image' => UploadedFile::fake()->image('unauthorized.jpg', 400, 400),
            ]);

        $response->assertStatus(404);
        $this->assertEmpty(Storage::disk('lost_found_private')->allFiles());
    }

    public function test_authenticated_student_can_withdraw_own_claim(): void
    {
        $student = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student, 'student')
            ->postJson(route('shop.student.lost_found.claims.withdraw', $claimId));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ClaimStatus::WITHDRAWN->value);

        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claimId,
            'status' => ClaimStatus::WITHDRAWN->value,
        ]);
    }

    public function test_cross_student_withdrawal_returns_404_existence_hiding(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $user = $this->createUser();
        $category = $this->createCategory();
        $item = $this->createFoundItem($user, $category);

        $storeResponse = $this->actingAs($student1, 'student')
            ->postJson(route('shop.student.lost_found.claims.store'), [
                'found_item_id' => $item->id,
            ]);
        $claimId = $storeResponse->json('data.id');

        $response = $this->actingAs($student2, 'student')
            ->postJson(route('shop.student.lost_found.claims.withdraw', $claimId));

        $response->assertStatus(404);

        $this->assertDatabaseHas('lost_found_claims', [
            'id' => $claimId,
            'status' => ClaimStatus::SUBMITTED->value,
        ]);
    }
}
